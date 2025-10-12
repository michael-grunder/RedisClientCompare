<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class AppendCommand extends KeyValueCommand
{
    protected const NAME = 'APPEND';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => false,
    ];
}
