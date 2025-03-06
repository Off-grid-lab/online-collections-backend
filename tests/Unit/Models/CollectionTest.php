<?php

namespace Tests\Unit\Models;

use App\Models\Collection;
use Tests\TestCase;

class CollectionTest extends TestCase
{
    public function test_item_filter_from_url()
    {
        $collection = Collection::factory()->make([
            'url' => 'https://sbirky.moravska-galerie.cz/?work_type=maliarstvo|fotografia',
        ]);

        $expected = [
            'work_type' => ['maliarstvo', 'fotografia'],
        ];
        $this->assertEquals($expected, $collection->item_filter);
    }
}
