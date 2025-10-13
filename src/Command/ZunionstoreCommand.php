<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class ZunionstoreCommand extends ZsetCombinationCommand
{
    protected const NAME = 'ZUNIONSTORE';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        return $this->buildCombinationArguments(true, false);
    }

    protected function generateClusterArguments(): array
    {
        return $this->buildClusterCombinationArguments(true, false);
    }
}
