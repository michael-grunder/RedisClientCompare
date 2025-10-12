<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class RpoplpushCommand extends KeyCommand
{
    protected const NAME = 'RPOPLPUSH';
    protected const ATTRIBUTES = [
        'data_type' => 'list',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        return [$this->randomKey(), $this->randomKey()];
    }
}
