<?php

namespace App\Models;

use App\Models\Concerns\Publishable;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Collection extends Model implements TranslatableContract
{
    use HasFactory;
    use Publishable;
    use Translatable;

    protected array $translatedAttributes = ['name', 'type', 'text', 'url'];

    protected $casts = [
        'published_at' => 'datetime',
        'frontends' => 'array',
    ];

    protected $attributes = [
        'featured' => false,
        'view_count' => 0,
    ];

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class)
            ->withPivot('order')
            ->orderBy('order');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getItemFilterAttribute(): ?array
    {
        if (! $this->url) {
            return null;
        }

        $url = parse_url($this->url);
        parse_str($url['query'] ?? '', $query);

        $filter = collect($query)
            ->filter(fn ($value) => is_string($value))
            ->map(
                fn ($value, $attribute) => str($value)->contains('|') ? explode('|', $value) : $value
            );

        return $filter->toArray();
    }
}
