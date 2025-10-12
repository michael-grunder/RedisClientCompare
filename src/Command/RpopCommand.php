<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class RpopCommand extends KeyCommand
{
    protected const NAME = 'RPOP';
    protected const ATTRIBUTES = [
        'data_type' => 'list',
        'readonly' => false,
    ];
}
