<?php

namespace Tests\Feature\Api\V1;

use App\Models\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_index()
    {
        $collection = Collection::factory()
            ->published()
            ->create([
                'url' => 'https://sbirky.moravska-galerie.cz/?author=author-1',
            ]);

        $response = $this->get(route('api.v1.collections.index'));
        $response->assertJsonCount(1, 'data')->assertJsonPath('data.0', [
            'id' => $collection->id,
            'name' => $collection->name,
            'text' => $collection->text,
        ]);
    }

    public function test_index_featured()
    {
        $featured = Collection::factory()
            ->published()
            ->featured()
            ->create();
        Collection::factory()
            ->published()
            ->create();

        $url = route('api.v1.collections.index', [
            'featured' => true,
            'size' => 2,
        ]);
        $response = $this->get($url);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $featured->id);
    }

    public function test_show()
    {
        $collection = Collection::factory()->create([
            'url' => 'https://sbirky.moravska-galerie.cz/?author=author-1',
        ]);

        $url = route('api.v1.collections.show', $collection);
        $response = $this->getJson($url);
        $response->assertJsonPath('data', [
            'id' => $collection->id,
            'name' => $collection->name,
            'text' => $collection->text,
            'item_filter' => [
                'author' => 'author-1',
            ],
        ]);
    }
}
