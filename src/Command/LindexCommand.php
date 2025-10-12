<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class LindexCommand extends KeyCommand
{
    protected const NAME = 'LINDEX';
    protected const ATTRIBUTES = [
        'data_type' => 'list',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        return [$this->randomKey(), random_int(-10, 50)];
    }
}
