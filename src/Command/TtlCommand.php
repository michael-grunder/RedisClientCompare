<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class TtlCommand extends KeyCommand
{
    protected const NAME = 'TTL';
    protected const ATTRIBUTES = [
        'data_type' => 'generic',
        'readonly' => true,
    ];
}
