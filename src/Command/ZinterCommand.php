<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class ZinterCommand extends ZsetCombinationCommand
{
    protected const NAME = 'ZINTER';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        return $this->buildCombinationArguments(false, true);
    }
}

