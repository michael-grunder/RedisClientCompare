<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class LlenCommand extends KeyCommand
{
    protected const NAME = 'LLEN';
    protected const ATTRIBUTES = [
        'data_type' => 'list',
        'readonly' => true,
    ];
}
