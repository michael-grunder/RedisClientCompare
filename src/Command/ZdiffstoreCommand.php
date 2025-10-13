<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class ZdiffstoreCommand extends Command
{
    protected const NAME = 'ZDIFFSTORE';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        $numKeys = random_int(1, 5);
        $keys = [];

        for ($i = 0; $i < $numKeys; $i++) {
            $keys[] = $this->randomScalarKey();
        }

        return array_merge([$this->randomScalarKey(), $numKeys], $keys);
    }

    protected function generateClusterArguments(): array
    {
        $numKeys = random_int(1, 5);
        $tag = $this->randomClusterSlotTag();
        $destination = $this->randomClusterKey($tag);
        $keys = $this->randomClusterKeySet($numKeys, $tag, null, false);

        return array_merge([$destination, $numKeys], $keys);
    }
}

