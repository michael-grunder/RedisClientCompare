<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class HstrlenCommand extends KeyFieldCommand
{
    protected const NAME = 'HSTRLEN';
    protected const ATTRIBUTES = [
        'data_type' => 'hash',
        'readonly' => true,
    ];
}

