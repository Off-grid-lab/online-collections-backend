<?php

namespace App\Http\Controllers\Api\V1;

use App\Facades\Frontend;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Repositories\ItemRepository;
use Elastic\ScoutDriverPlus\Builders\QueryBuilderInterface;
use Elastic\ScoutDriverPlus\Support\Query;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ItemController extends Controller
{
    private array $filterables = [
        'author',
        'authors.name',
        'authors.authority.id',
        'topic',
        'work_type',
        'medium',
        'technique',
        'gallery',
        'has_image',
        'has_iip',
        'has_text',
        'is_free',
        'tag',
        'credit',
        'related_work',
        'is_for_reproduction',
        'contributor',
        'additionals.category.keyword',
        'additionals.frontend.keyword',
        'additionals.set.keyword',
        'additionals.location.keyword',
        'exhibition',
        'box',
        'location',
        'location.tree',
    ];

    private array $rangeables = ['date_earliest', 'date_latest', 'additionals.order'];

    private array $sortables = [
        'date_earliest',
        'date_latest',
        'updated_at',
        'created_at',
        'title',
        'author',
        'view_count',
        'additionals.order',
    ];

    public function index(Request $request)
    {
        $filter = (array) $request->get('filter');
        $sort = (array) $request->get('sort');
        $size = (int) $request->get('size', 1);
        $q = (string) $request->get('q');

        $query = $this->createQueryBuilder($q, $filter)->buildQuery();

        if (array_key_exists('random', $sort)) {
            $query = ItemRepository::buildRandomSortQuery($query, $request->get('page', 1) == 1);
        }

        $searchRequest = Item::searchQuery($query);
        $searchRequest->trackTotalHits(true);

        $sorts = collect($sort)
            ->only($this->sortables)
            ->filter(fn ($direction) => in_array($direction, ['asc', 'desc']));

        $sorts->each(function ($direction, $field) use ($searchRequest) {
            $searchRequest->sort($field, $direction);
        });

        if ($sorts->isEmpty()) {
            $searchRequest->sortRaw(ItemRepository::buildDefaultSortQuery());
        }

        $response = $searchRequest
            ->paginate($size)
            ->withQueryString()
            ->onlyDocuments()
            ->toArray();

        return [
            ...$response,
            'data' => collect($response['data'])->map(
                fn ($document) => [
                    ...$document,
                    'content' => [
                        ...$document['content'],
                        'authors_formatted' => collect($document['content']['author'])->map(
                            fn ($author) => Item::formatName($author)
                        ),
                        //                        'authors' => collect($document['content']['authors'])->map(
                        //                            fn($a) => [...$a, 'name_formatted' => Item::formatName($a['name'])]
                        //                        ),
                    ],
                ]
            ),
        ];
    }

    public function aggregations(Request $request)
    {
        $filter = (array) $request->get('filter');
        $terms = (array) $request->get('terms');
        $min = (array) $request->get('min');
        $max = (array) $request->get('max');
        $size = (int) $request->get('size', 1);
        $q = (string) $request->get('q');

        $aggregationsQuery = [];

        foreach ($terms as $aggregationName => $field) {
            if ($field === 'authors') {
                $aggregationsQuery[$aggregationName]['aggregations']['filtered'] = [
                    'nested' => [
                        'path' => 'authors',
                    ],
                    'aggs' => [
                        'authors' => [
                            'multi_terms' => [
                                'terms' => [
                                    [
                                        'field' => 'authors.name',
                                    ],
                                    [
                                        'field' => 'authors.authority.id',
                                        'missing' => '',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ];

                continue;
            }
            $aggregationsQuery[$aggregationName]['aggregations']['filtered']['terms'] = [
                'field' => $field,
                'size' => $size,
            ];
        }

        foreach ($min as $aggregationName => $field) {
            $aggregationsQuery[$aggregationName]['aggregations']['filtered']['min'] = [
                'field' => $field,
            ];
        }

        foreach ($max as $aggregationName => $field) {
            $aggregationsQuery[$aggregationName]['aggregations']['filtered']['max'] = [
                'field' => $field,
            ];
        }

        // Add filter terms to each aggregation
        // Based on https://madewithlove.com/blog/faceted-search-using-elasticsearch/
        foreach (array_keys($aggregationsQuery) as $term) {
            $termFilter = Arr::except($filter, $term);

            // Exclude date_latest for date_earliest and vice versa
            if ($term === 'date_earliest') {
                $termFilter = Arr::except($termFilter, 'date_latest');
            }
            if ($term === 'date_latest') {
                $termFilter = Arr::except($termFilter, 'date_earliest');
            }

            $aggregationsQuery[$term]['filter'] = $this->createQueryBuilder(
                $q,
                $termFilter
            )->buildQuery();
        }

        $searchRequest = Item::searchQuery()
            ->size(0) // No documents are needed, only aggregations
            ->aggregateRaw([
                'all_items' => [
                    'global' => (object) [],
                    'aggregations' => (object) $aggregationsQuery,
                ],
            ]);

        return collect(Arr::get($searchRequest->execute()->raw(), 'aggregations.all_items'))
            ->only(array_keys($aggregationsQuery))
            ->map(function (array $aggregation, $name) {
                if ($name === 'authors') {
                    return collect(Arr::get($aggregation, 'filtered.authors.buckets'))->map(
                        function (array $bucket) {
                            [$name, $authority_id] = $bucket['key'];
                            $authority = $authority_id === '' ? null : ['id' => $authority_id];

                            return [
                                'name' => $name,
                                'authority' => $authority,
                                'count' => $bucket['doc_count'],
                            ];
                        }
                    );
                }

                if (Arr::has($aggregation, 'filtered.value')) {
                    return Arr::get($aggregation, 'filtered.value');
                }

                return collect(Arr::get($aggregation, 'filtered.buckets'))->map(
                    fn (array $bucket) => [
                        'value' => $bucket['key'],
                        'count' => $bucket['doc_count'],
                    ]
                );
            });
    }

    public function show(string $id)
    {
        $query = ItemRepository::idQuery($id);
        $items = Item::searchQuery($query)->execute();

        if (! $items->total()) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $document = $items
            ->documents()
            ->first()
            ->toArray();

        $model = $items->models()->first();

        return [
            ...$document,
            'content' => [
                ...$document['content'],
                ...$model->only([
                    'relationship_type',
                    'state_edition',
                    'inscription',
                    'acquisition_date',
                    'work_type_tree',
                    'object_type_tree',
                ]),
            ],
        ];
    }

    public function incrementViewCount(string $id)
    {
        Item::findOrFail($id)->increment('view_count');
    }

    public function suggestions(Request $request)
    {
        $size = (int) $request->get('size', 1);
        $q = (string) $request->get('q');

        $query = ItemRepository::buildSuggestionsQuery($q);
        $documents = Item::searchQuery($query)
            ->size($size)
            ->execute()
            ->documents();

        return ['data' => $documents];
    }

    public function similar(Request $request, string $id)
    {
        $item = Item::findOrFail($id);
        $size = (int) $request->get('size', 1);

        $query = app(ItemRepository::class)->buildSimilarQuery($item);
        $items = Item::searchQuery($query)
            ->size($size)
            ->execute();

        return ['data' => $items->documents()];
    }

    protected function createQueryBuilder($q, $filter): QueryBuilderInterface
    {
        $builder = Query::bool();
        $authorsBuilder = null;

        if ($q) {
            $query = Query::multiMatch()
                ->fields(['title.*', 'description.*', 'identifier', 'id'])
                ->query($q);
            $builder->must($query);
        }

        foreach ($filter as $field => $value) {
            if ($field === 'authors.name' || $field === 'authors.authority.id') {
                $authorsBuilder ??= Query::bool();
                $authorsBuilder->should(
                    is_array($value)
                        ? ['terms' => [$field => $value]]
                        : ['term' => [$field => $value]]
                );

                continue;
            }

            if (is_string($value) && in_array($field, $this->filterables, true)) {
                if ($value === 'false') {
                    $value = false;
                }
                if ($value === 'true') {
                    $value = true;
                }
                $builder->filter(['term' => [$field => $value]]);

                continue;
            }
            if (is_array($value) && in_array($field, $this->filterables, true)) {
                $builder->filter(['terms' => [$field => $value]]);

                continue;
            }
            if (is_array($value) && in_array($field, $this->rangeables, true)) {
                $range = collect($value)
                    ->only(['lt', 'lte', 'gt', 'gte'])
                    ->transform(function ($value) {
                        return (string) $value;
                    })
                    ->all();
                $builder->filter(['range' => [$field => $range]]);

                continue;
            }
        }

        if ($authorsBuilder) {
            $builder->filter(
                Query::nested()
                    ->path('authors')
                    ->query($authorsBuilder)
            );
        }

        return $builder->filter(['term' => ['frontend' => Frontend::get()]]);
    }
}
