<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class ExpireCommand extends KeyCommand
{
    protected const NAME = 'EXPIRE';
    protected const ATTRIBUTES = [
        'data_type' => 'generic',
        'readonly' => false,
        'interacts_with_expiration' => true,
    ];

    protected function generateArguments(): array
    {
        return [$this->randomKey(), random_int(1, 3600)];
    }
}
