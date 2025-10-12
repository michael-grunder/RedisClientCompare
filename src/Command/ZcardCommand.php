<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class ZcardCommand extends KeyCommand
{
    protected const NAME = 'ZCARD';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => true,
    ];
}
