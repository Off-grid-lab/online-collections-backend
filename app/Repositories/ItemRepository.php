<?php

namespace App\Repositories;

use App\Facades\Frontend;
use App\Models\Item;
use Elastic\ScoutDriverPlus\Builders\QueryBuilderInterface;
use Elastic\ScoutDriverPlus\Support\Query;
use Illuminate\Support\Facades\Session;

class ItemRepository extends AbstractRepository
{
    protected string $modelClass = Item::class;

    protected string $index = 'items';

    public function reindexAllLocales(): int
    {
        $processedCount = 0;

        $query = Item::with(['images', 'translations']);

        $query
            ->lazy()
            ->tapEach(function () use (&$processedCount) {
                $processedCount++;
            })
            ->flatMap(function (Item $item) {
                $operations = [];

                foreach ($this->locales as $locale) {
                    // Action
                    $operations[] = [
                        'index' => [
                            '_index' => $this->getLocalizedIndexName($locale),
                            '_id' => $item->getKey(),
                        ],
                    ];

                    // Data
                    $operations[] = $item->getIndexedData($locale);
                }

                return $operations;
            })
            // chunk size = 2 operations * number of locales * 200 items
            ->chunk(2 * count($this->locales) * 200)
            ->each(function ($operations) use (&$processedCount) {
                $this->elasticsearch->bulk(['body' => $operations]);

                // Progress report
                if (app()->runningInConsole()) {
                    echo date('h:i:s').' '.$processedCount."\n";
                }
            });

        return $processedCount;
    }

    public static function buildDefaultSortQuery(): array
    {
        return [
            '_score',
            ['has_image' => ['order' => 'desc']],
            ['has_iip' => ['order' => 'desc']],
            ['updated_at' => ['order' => 'desc']],
            ['created_at' => ['order' => 'desc']],
        ];
    }

    public static function buildSuggestionsQuery(string $search): array
    {
        $query = [
            'bool' => [
                'must' => [
                    'multi_match' => [
                        'query' => $search,
                        'type' => 'cross_fields',
                        'fields' => ['identifier', 'title.suggest', 'author.suggest'],
                        'operator' => 'and',
                    ],
                ],
                'should' => [
                    ['term' => ['has_image' => true]],
                ],
            ],
        ];

        $query['bool']['filter'] = [
            ['term' => ['frontend' => Frontend::get()]],
        ];

        return $query;
    }

    public function buildSimilarQuery(Item $item, ?string $locale = null): array
    {
        $query = [
            'bool' => [
                'must' => [
                    [
                        'more_like_this' => [
                            'like' => [
                                [
                                    '_index' => $this->getLocalizedIndexName($locale),
                                    '_id' => $item->id,
                                ],
                            ],
                            'fields' => [
                                'author.folded',
                                'title',
                                'title.stemmed',
                                'description.stemmed',
                                'tag.folded',
                                'place',
                                'technique',
                            ],
                            'min_term_freq' => 1,
                            'min_doc_freq' => 1,
                            'minimum_should_match' => 1,
                            'min_word_length' => 1,
                        ],
                    ],
                    [
                        'term' => [
                            'has_image' => [
                                'value' => true,
                                'boost' => 10,
                            ],
                        ],
                    ],
                ],
                'should' => [
                    [
                        'term' => ['has_iip' => true],
                    ],
                ],
            ],
        ];

        if ($item->related_work) {
            $query['bool']['must_not'] = [
                [
                    'term' => [
                        'related_work' => [
                            'value' => $item->related_work,
                        ],
                    ],
                ],
            ];
        }

        $query['bool']['filter'] = [
            ['term' => ['frontend' => Frontend::get()]],
        ];

        return $query;
    }

    public static function buildRandomSortQuery(array $query, $firstPage = true): array
    {
        if ($firstPage) {
            Session::put('ItemRepository::random-seed', mt_rand());
        }

        return [
            'function_score' => [
                'query' => $query,
                'functions' => [
                    [
                        'random_score' => [
                            'seed' => Session::get('ItemRepository::random-seed'),
                            'field' => '_seq_no',
                        ],
                    ],
                    [
                        'field_value_factor' => [
                            'field' => 'has_image',
                            'factor' => 10,
                        ],
                    ],
                ],
                'boost_mode' => 'sum',
            ],

        ];
    }

    public static function idQuery(string $id): QueryBuilderInterface
    {
        return Query::bool()
            ->must(Query::ids()->values([$id]))
            ->filter(['term' => ['frontend' => Frontend::get()]]);
    }

    protected function getIndexConfig(?string $locale = null): array
    {
        return config('elasticsearch.index.items')[$this->getLocale($locale)];
    }

    protected function getMappingConfig(?string $locale = null): array
    {
        return config('elasticsearch.mapping.items')[$this->getLocale($locale)];
    }
}
