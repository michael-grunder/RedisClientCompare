<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class MsetnxCommand extends Command
{
    protected const NAME = 'MSETNX';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        $pairs = [];
        $keys = [];
        $count = random_int(1, 4);

        while (count($keys) < $count) {
            $key = $this->randomScalarKey();
            if (in_array($key, $keys, true)) {
                continue;
            }

            $keys[] = $key;
        }

        foreach ($keys as $key) {
            $pairs[] = $key;
            $pairs[] = $this->randomValue();
        }

        return $pairs;
    }
}

