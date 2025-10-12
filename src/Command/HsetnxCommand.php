<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class HsetnxCommand extends KeyFieldValueCommand
{
    protected const NAME = 'HSETNX';
    protected const ATTRIBUTES = [
        'data_type' => 'hash',
        'readonly' => false,
    ];
}
