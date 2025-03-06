<?php

namespace App\Repositories;

use App\Models\Contracts\IndexableModel;
use App\Repositories\Contracts\Repository;
use Elastic\Client\ClientBuilderInterface;
use Elastic\Elasticsearch\Client;
use Illuminate\Container\Attributes\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

abstract class AbstractRepository implements Repository
{
    protected string $index;

    protected string $modelClass;

    protected Client $elasticsearch;

    public function __construct(
        #[Config('translatable.locales')] protected readonly array $locales,
        #[Config('elasticsearch.common.prefix')] private readonly ?string $prefix,
        private readonly ClientBuilderInterface $clientBuilder,
        private readonly ?string $version = null
    ) {
        $this->elasticsearch = $clientBuilder->default();
    }

    public static function buildNewVersionNumber(): string
    {
        return Carbon::now()->timestamp;
    }

    public function buildWithVersion(string $version): static
    {
        return new static(
            $this->locales,
            $this->prefix,
            $this->clientBuilder,
            $version
        );
    }

    public function index(Model|IndexableModel $model, ?string $locale = null): void
    {
        $this->elasticsearch->index([
            'index' => $this->getLocalizedIndexName($locale),
            'id' => $model->getKey(),
            'body' => $model->getIndexedData($locale),
        ]);
    }

    public function delete(Model $model, ?string $locale = null): void
    {
        $this->elasticsearch->delete([
            'index' => $this->getLocalizedIndexName($locale),
            'id' => $model->getKey(),
        ]);
    }

    public function deleteIndex(?string $locale = null): void
    {
        $indexName = $this->version ? $this->getVersionedIndexName($locale) : $this->fetchVersionedIndexName($locale);

        $this->elasticsearch->indices()->delete([
            'index' => $indexName,
        ]);
    }

    public function createIndex(?string $locale = null): void
    {
        $this->elasticsearch->indices()->create([
            'index' => $this->getLocalizedIndexName($locale),
            'body' => $this->getIndexConfig($locale),
        ]);
    }

    public function createIndexAlias(?string $locale = null): void
    {
        $aliasName = $this->getIndexAliasName($locale);
        $indexName = $this->version ? $this->getVersionedIndexName($locale) : $this->fetchVersionedIndexName($locale);

        $this->elasticsearch->indices()->putAlias([
            'index' => $indexName,
            'name' => $aliasName,
        ]);
    }

    public function createMapping(?string $locale = null): void
    {
        $this->elasticsearch->indices()->putMapping([
            'index' => $this->getLocalizedIndexName($locale),
            'body' => $this->getMappingConfig($locale),
        ]);
    }

    public function refreshIndex(?string $locale = null): void
    {
        $this->elasticsearch->indices()->refresh([
            'index' => $this->getLocalizedIndexName($locale),
        ]);
    }

    public function indexAllLocales(Model $model): void
    {
        foreach ($this->locales as $locale) {
            $this->index($model, $locale);
        }
    }

    public function deleteAllLocales(Model $model): void
    {
        foreach ($this->locales as $locale) {
            $this->delete($model, $locale);
        }
    }

    public function getLocalizedIndexName(?string $locale = null): string
    {
        if ($this->version) {
            return $this->getVersionedIndexName($locale);
        }

        return $this->getIndexAliasName($locale);
    }

    public function getIndexAliasName(?string $locale = null): string
    {
        return sprintf(
            '%s%s_%s',
            $this->prefix,
            $this->index,
            $this->getLocale($locale)
        );
    }

    public function indexExists(?string $locale = null): bool
    {
        return $this->elasticsearch
            ->indices()
            ->exists(['index' => $this->getLocalizedIndexName($locale)])
            ->asBool();
    }

    public function getVersionedIndexName(?string $locale = null): string
    {
        return sprintf(
            '%s%s_%s_%s',
            $this->prefix,
            $this->index,
            $this->getLocale($locale),
            $this->version
        );
    }

    public function fetchVersionedIndexName(?string $locale = null): string
    {
        $index = $this->elasticsearch->indices()->get([
            'index' => $this->getIndexAliasName($locale),
        ])->asArray();

        return array_keys($index)[0];
    }

    protected function getLocale(?string $locale = null): string
    {
        return $locale ?? app()->getLocale();
    }

    abstract public function reindexAllLocales(): int;

    abstract protected function getIndexConfig(?string $locale = null): array;

    abstract protected function getMappingConfig(?string $locale = null): array;
}
