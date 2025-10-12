<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class ZscoreCommand extends KeyCommand
{
    protected const NAME = 'ZSCORE';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        return [$this->randomKey(), $this->randomZsetMember()];
    }
}
