<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class ScardCommand extends KeyCommand
{
    protected const NAME = 'SCARD';
    protected const ATTRIBUTES = [
        'data_type' => 'set',
        'readonly' => true,
    ];
}
