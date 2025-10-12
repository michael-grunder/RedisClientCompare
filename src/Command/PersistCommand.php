<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class PersistCommand extends KeyCommand
{
    protected const NAME = 'PERSIST';
    protected const ATTRIBUTES = [
        'data_type' => 'generic',
        'readonly' => false,
    ];
}
