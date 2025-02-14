<?php

namespace App\Services;

use App\Enums\FrontendEnum;

class FrontendService
{
    public function __construct(public FrontendEnum $current = FrontendEnum::DEFAULT) {}

    public function set(FrontendEnum $frontend): void
    {
        $this->current = $frontend;
    }

    public function get(): FrontendEnum
    {
        return $this->current;
    }
}
