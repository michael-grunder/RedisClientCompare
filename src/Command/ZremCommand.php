<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class ZremCommand extends KeyCommand
{
    protected const NAME = 'ZREM';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        return [$this->randomKey(), $this->randomValue()];
    }
}
