<?php

namespace Feature\Api\V1;

use App\Models\Collection;
use App\Models\Item;
use App\Repositories\ItemRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\RecreateSearchIndex;
use Tests\TestCase;

class CollectionItemsTest extends TestCase
{
    use RecreateSearchIndex, RefreshDatabase;

    public function test_index_moravska_galerie_url()
    {
        Item::factory(2)->create(['author' => 'author-1']);
        Item::factory()->create(['author' => 'author-2']);
        app(ItemRepository::class)->refreshIndex();

        $collection = Collection::factory()->create([
            'url' => 'https://sbirky.moravska-galerie.cz/?author=author-1',
        ]);

        $url = route('api.v1.collections.items.index', [
            'collection' => $collection,
            'size' => 3,
        ]);
        $response = $this->get($url);
        $response->assertJsonCount(2, 'data');
    }
}
