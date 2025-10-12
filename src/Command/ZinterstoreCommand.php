<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class ZinterstoreCommand extends ZsetCombinationCommand
{
    protected const NAME = 'ZINTERSTORE';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        return $this->buildCombinationArguments(true, false);
    }
}

