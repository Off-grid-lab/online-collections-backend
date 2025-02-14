<?php

namespace App\Observers;

use App\Models\Item;
use App\Repositories\ItemRepository;

readonly class ItemObserver
{
    public function __construct(
        private ItemRepository $itemRepository,
    ) {}

    public function saved(Item $item): void
    {
        $this->itemRepository->indexAllLocales($item->fresh());
    }

    public function deleted(Item $item): void
    {
        $this->itemRepository->deleteAllLocales($item);
    }
}
