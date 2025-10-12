<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class ZunionCommand extends ZsetCombinationCommand
{
    protected const NAME = 'ZUNION';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        return $this->buildCombinationArguments(false, true);
    }
}

