<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class DecrCommand extends KeyCommand
{
    protected const NAME = 'DECR';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => false,
    ];
}
