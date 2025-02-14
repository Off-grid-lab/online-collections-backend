<?php

namespace App\Models\Contracts;

interface IndexableModel
{
    public function getIndexedData(string $locale): array;
}
