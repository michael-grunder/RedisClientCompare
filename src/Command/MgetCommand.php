<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class MgetCommand extends Command
{
    protected const NAME = 'MGET';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        $keys = [];
        $count = random_int(1, 5);
        for ($i = 0; $i < $count; $i++) {
            $keys[] = $this->randomScalarKey();
        }

        return $keys;
    }

    protected function generateClusterArguments(): array
    {
        $count = random_int(1, 5);

        return $this->randomClusterKeySet($count, null, null, false);
    }
}
