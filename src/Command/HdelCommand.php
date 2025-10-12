<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class HdelCommand extends KeyCommand
{
    protected const NAME = 'HDEL';
    protected const ATTRIBUTES = [
        'data_type' => 'hash',
        'readonly' => false,
    ];
}
