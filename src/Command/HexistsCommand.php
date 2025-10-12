<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class HexistsCommand extends KeyFieldCommand
{
    protected const NAME = 'HEXISTS';
    protected const ATTRIBUTES = [
        'data_type' => 'hash',
        'readonly' => true,
    ];
}

