<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class HvalsCommand extends KeyCommand
{
    protected const NAME = 'HVALS';
    protected const ATTRIBUTES = [
        'data_type' => 'hash',
        'readonly' => true,
    ];
}

