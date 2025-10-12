<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class GetCommand extends KeyCommand
{
    protected const NAME = 'GET';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => true,
    ];
}
