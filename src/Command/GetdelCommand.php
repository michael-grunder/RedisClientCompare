<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class GetdelCommand extends KeyCommand
{
    protected const NAME = 'GETDEL';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => false,
    ];
}

