<?php

namespace Tests;

use App\Repositories\ItemRepository;
use Elastic\Elasticsearch\Exception\ClientResponseException;

trait RecreateSearchIndex
{
    /**
     * Define hooks to migrate the database before and after each test.
     */
    public function setUpRecreateSearchIndex(): void
    {
        $repository = $this->app->make(ItemRepository::class);
        $locale = app()->getLocale();

        // Drop previous index if it exists
        try {
            $repository->deleteIndex($locale);
        } catch (ClientResponseException) {
        }

        $repository->createIndex($locale);
        $repository->createMapping($locale);
    }
}
