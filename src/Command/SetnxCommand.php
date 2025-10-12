<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class SetnxCommand extends KeyValueCommand
{
    protected const NAME = 'SETNX';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => false,
    ];
}
