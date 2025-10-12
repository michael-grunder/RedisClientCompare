<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class IncrbyCommand extends KeyCommand
{
    protected const NAME = 'INCRBY';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        return [$this->randomKey(), random_int(1, 1000)];
    }
}
