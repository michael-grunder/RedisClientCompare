<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class HkeysCommand extends KeyCommand
{
    protected const NAME = 'HKEYS';
    protected const ATTRIBUTES = [
        'data_type' => 'hash',
        'readonly' => true,
    ];
}

