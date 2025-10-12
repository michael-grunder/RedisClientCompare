<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class IncrbyfloatCommand extends KeyCommand
{
    protected const NAME = 'INCRBYFLOAT';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        return [$this->randomKey(), $this->randomFloat()];
    }
}
