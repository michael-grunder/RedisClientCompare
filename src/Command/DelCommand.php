<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class DelCommand extends KeyCommand
{
    protected const NAME = 'DEL';
    protected const ATTRIBUTES = [
        'data_type' => 'generic',
        'readonly' => false,
    ];
}
