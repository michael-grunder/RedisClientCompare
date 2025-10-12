<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class HgetCommand extends KeyCommand
{
    protected const NAME = 'HGET';
    protected const ATTRIBUTES = [
        'data_type' => 'hash',
        'readonly' => true,
    ];
}
