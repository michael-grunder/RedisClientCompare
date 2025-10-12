<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class LremCommand extends KeyCommand
{
    protected const NAME = 'LREM';
    protected const ATTRIBUTES = [
        'data_type' => 'list',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        return [
            $this->randomKey(),
            random_int(-3, 3),
            $this->randomValue(),
        ];
    }
}
