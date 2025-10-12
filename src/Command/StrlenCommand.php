<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class StrlenCommand extends KeyCommand
{
    protected const NAME = 'STRLEN';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => true,
    ];
}

