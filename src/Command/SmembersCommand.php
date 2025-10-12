<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class SmembersCommand extends KeyCommand
{
    protected const NAME = 'SMEMBERS';
    protected const ATTRIBUTES = [
        'data_type' => 'set',
        'readonly' => true,
    ];
}
