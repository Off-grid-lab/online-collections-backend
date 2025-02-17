<?php

namespace App\Models;

use App\Models\Contracts\IndexableModel;
use App\Repositories\ItemRepository;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Elastic\ScoutDriverPlus\Searchable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model implements IndexableModel, TranslatableContract
{
    use HasFactory, Searchable, Translatable;

    protected $keyType = 'string';

    public $incrementing = false;

    protected array $translatedAttributes = [
        'title',
        'description',
        'description_source',
        'description_source_link',
        'work_type',
        'object_type',
        'work_level',
        'topic',
        'subject',
        'measurement',
        'dating',
        'medium',
        'technique',
        'inscription',
        'place',
        'state_edition',
        'gallery',
        'credit',
        'relationship_type',
        'related_work',
        'additionals',
        'style_period',
        'current_location',
    ];

    protected $casts = [
        'frontends' => 'array',
    ];

    const string TREE_DELIMITER = '/';

    public static function formatName(string $name): string
    {
        return preg_replace('/^([^,]*),\s*(.*)$/', '$2 $1', $name);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ItemImage::class)
            ->orderBy('order_column')
            ->orderBy('iipimg_url');
    }

    public function searchableAs(): string
    {
        return app(ItemRepository::class)->getLocalizedIndexName();
    }

    public function authors(): Attribute
    {
        return Attribute::get(fn () => $this->makeArray($this->author));
    }

    public function getIndexedData(string $locale): array
    {
        $formatTree = function ($serializedTrees) {
            $unserialized = $this->unserializeTrees($serializedTrees);

            return array_map(function ($tree) {
                return end($tree)['path'];
            }, $unserialized);
        };

        return [
            'id' => $this->id,
            'identifier' => $this->identifier,
            'author' => $this->authors,
            'date_earliest' => $this->date_earliest,
            'date_latest' => $this->date_latest,
            'has_image' => (bool) $this->has_image,
            'has_iip' => $this->has_iip,
            'has_text' => (bool) $this["description:$locale"],
            'view_count' => $this->view_count,
            'work_type' => $formatTree($this["work_type:$locale"]),
            'object_type' => $formatTree($this["object_type:$locale"]),
            'image_ratio' => $this->image_ratio,
            'title' => $this["title:$locale"],
            'description' => (! empty($this["description:$locale"])) ? strip_tags($this["description:$locale"]) : '',
            'topic' => $this->makeArray($this["topic:$locale"]),
            'place' => $this->makeArray($this["place:$locale"]),
            'measurement' => $this["measurement:$locale"],
            'dating' => $this["dating:$locale"],
            'medium' => $this->makeArray($this["medium:$locale"]),
            'technique' => $this->makeArray($this["technique:$locale"]),
            'gallery' => $this["gallery:$locale"],
            'credit' => $this["credit:$locale"],
            'contributor' => $this->contributor,
            'related_work' => $this["related_work:$locale"],
            'exhibition' => $this->exhibition,
            'box' => $this->box,
            'location' => $this->location,
            'additionals' => $this["additionals:$locale"],
            'images' => $this->images->map(fn (ItemImage $image) => $image->iipimg_url),
            'frontend' => $this->frontends,
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function workTypeTree(): Attribute
    {
        return Attribute::get(fn () => $this->unserializeTrees($this->work_type));
    }

    public function objectTypeTree(): Attribute
    {
        return Attribute::get(fn () => $this->unserializeTrees($this->object_type));
    }

    private function unserializeTrees($serializedTrees): array
    {
        $trees = $this->makeArray($serializedTrees);

        return array_map(function ($tree) {
            $stack = [];

            return array_map(function ($part) use (&$stack) {
                $stack[] = $part;

                return [
                    'name' => $part,
                    'path' => implode(self::TREE_DELIMITER, $stack),
                ];
            }, explode(', ', $tree));
        }, $trees);
    }

    private function makeArray(?string $value): array
    {
        return str($value)
            ->explode(';')
            ->map(fn (string $item) => trim($item))
            ->toArray();
    }
}
