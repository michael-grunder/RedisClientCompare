<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class RpushCommand extends KeyCommand
{
    protected const NAME = 'RPUSH';
    protected const ATTRIBUTES = [
        'data_type' => 'list',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        $values = [];
        $count = random_int(1, 4);
        for ($i = 0; $i < $count; $i++) {
            $values[] = $this->randomValue();
        }

        return array_merge([$this->randomKey()], $values);
    }
}
