<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class ExistsCommand extends KeyCommand
{
    protected const NAME = 'EXISTS';
    protected const ATTRIBUTES = [
        'data_type' => 'generic',
        'readonly' => true,
    ];
}
