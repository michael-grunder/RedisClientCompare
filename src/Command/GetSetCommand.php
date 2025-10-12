<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class GetSetCommand extends KeyValueCommand
{
    protected const NAME = 'GETSET';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => false,
    ];
}
