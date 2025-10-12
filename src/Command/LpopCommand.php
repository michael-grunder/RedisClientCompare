<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class LpopCommand extends KeyCommand
{
    protected const NAME = 'LPOP';
    protected const ATTRIBUTES = [
        'data_type' => 'list',
        'readonly' => false,
    ];
}
