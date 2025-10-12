<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class HgetallCommand extends KeyCommand
{
    protected const NAME = 'HGETALL';
    protected const ATTRIBUTES = [
        'data_type' => 'hash',
        'readonly' => true,
    ];
}
