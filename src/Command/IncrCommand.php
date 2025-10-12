<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class IncrCommand extends KeyCommand
{
    protected const NAME = 'INCR';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => false,
    ];
}
