<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class SetCommand extends KeyValueCommand
{
    protected const NAME = 'SET';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => false,
    ];
}
