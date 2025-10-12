<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class HsetCommand extends KeyFieldValueCommand
{
    protected const NAME = 'HSET';
    protected const ATTRIBUTES = [
        'data_type' => 'hash',
        'readonly' => false,
    ];
}
