<?php

use App\Http\Controllers\Api\V1\CollectionController;
use App\Http\Controllers\Api\V1\CollectionItemController;
use App\Http\Controllers\Api\V1\ItemController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function () {
        Route::get('items', [ItemController::class, 'index'])->name('items.index');
        Route::get('items/aggregations', [ItemController::class, 'aggregations'])->name(
            'items.aggregations'
        );
        Route::get('items/suggestions', [ItemController::class, 'suggestions'])->name(
            'items.suggestions'
        );
        Route::get('items/{id}', [ItemController::class, 'show'])->name('items.show');
        Route::get('items/{id}/similar', [ItemController::class, 'similar'])->name(
            'items.similar'
        );
        Route::post('items/{id}/views', [ItemController::class, 'incrementViewCount'])->name(
            'items.views'
        );

        Route::apiResource('collections', CollectionController::class)
            ->names('collections')
            ->only(['index', 'show']);
        Route::apiResource('/collections/{collection}/items', CollectionItemController::class)
            ->names('collections.items')
            ->only(['index']);
    });
