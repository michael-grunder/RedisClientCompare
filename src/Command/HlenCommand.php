<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class HlenCommand extends KeyCommand
{
    protected const NAME = 'HLEN';
    protected const ATTRIBUTES = [
        'data_type' => 'hash',
        'readonly' => true,
    ];
}

