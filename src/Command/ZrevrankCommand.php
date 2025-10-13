<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class ZrevrankCommand extends KeyCommand
{
    protected const NAME = 'ZREVRANK';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        return [$this->randomKey(), $this->randomZsetMember()];
    }
}

