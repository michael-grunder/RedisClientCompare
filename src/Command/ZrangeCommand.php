<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class ZrangeCommand extends KeyCommand
{
    protected const NAME = 'ZRANGE';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        return [$this->randomKey(), '0', '-1'];
    }
}
