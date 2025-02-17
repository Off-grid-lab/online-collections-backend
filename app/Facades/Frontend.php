<?php

namespace App\Facades;

use App\Services\FrontendService;
use Illuminate\Support\Facades\Facade;

class Frontend extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FrontendService::class;
    }
}
