<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class HmsetCommand extends KeyCommand
{
    protected const NAME = 'HMSET';
    protected const ATTRIBUTES = [
        'data_type' => 'hash',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        $pairs = [];
        $count = random_int(1, 10);
        for ($i = 0; $i < $count; $i++) {
            $pairs[] = $this->randomField();
            $pairs[] = $this->randomValue();
        }

        return array_merge([$this->randomKey()], $pairs);
    }
}
