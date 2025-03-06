<?php

namespace App\Repositories\Contracts;

use Elastic\Client\ClientBuilderInterface;

interface Repository
{
    public function __construct(
        array $locales,
        ?string $prefix,
        ClientBuilderInterface $clientBuilder,
        ?string $version = null
    );
}
