<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class ZdiffCommand extends Command
{
    protected const NAME = 'ZDIFF';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        $numKeys = random_int(1, 5);
        $keys = [];

        for ($i = 0; $i < $numKeys; $i++) {
            $keys[] = $this->randomScalarKey();
        }

        $args = array_merge([$numKeys], $keys);

        if ($numKeys > 1 && random_int(0, 1) === 1) {
            $args[] = 'WITHSCORES';
        }

        return $args;
    }

    protected function generateClusterArguments(): array
    {
        $numKeys = random_int(1, 5);
        $tag = $this->randomClusterSlotTag();
        $keys = $this->randomClusterKeySet($numKeys, $tag, null, false);

        $args = array_merge([$numKeys], $keys);

        if ($numKeys > 1 && random_int(0, 1) === 1) {
            $args[] = 'WITHSCORES';
        }

        return $args;
    }
}

