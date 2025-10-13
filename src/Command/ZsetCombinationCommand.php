<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

abstract class ZsetCombinationCommand extends Command
{
    /**
     * @return array<int, int|float|string>
     */
    final protected function buildCombinationArguments(bool $withDestination, bool $withScoresAllowed): array
    {
        $numKeys = random_int(1, 5);
        $keys = [];

        for ($i = 0; $i < $numKeys; $i++) {
            $keys[] = $this->randomScalarKey();
        }

        $args = [];

        if ($withDestination) {
            $args[] = $this->randomScalarKey();
        }

        $args[] = $numKeys;
        $args = array_merge($args, $keys);

        if ($numKeys > 1 && random_int(0, 1) === 1) {
            $weights = [];
            for ($i = 0; $i < $numKeys; $i++) {
                $weights[] = $this->randomWeight();
            }

            $args[] = 'WEIGHTS';
            $args = array_merge($args, $weights);
        }

        if ($numKeys > 1 && random_int(0, 1) === 1) {
            $args[] = 'AGGREGATE';
            $args[] = $this->randomAggregate();
        }

        if ($withScoresAllowed && random_int(0, 1) === 1) {
            $args[] = 'WITHSCORES';
        }

        return $args;
    }

    /**
     * @return array<int, int|float|string>
     */
    final protected function buildClusterCombinationArguments(bool $withDestination, bool $withScoresAllowed): array
    {
        $numKeys = random_int(1, 5);
        $tag = $this->randomClusterSlotTag();
        $keys = $this->randomClusterKeySet($numKeys, $tag, null, false);

        $args = [];

        if ($withDestination) {
            $args[] = $this->randomClusterKey($tag);
        }

        $args[] = $numKeys;
        $args = array_merge($args, $keys);

        if ($numKeys > 1 && random_int(0, 1) === 1) {
            $weights = [];
            for ($i = 0; $i < $numKeys; $i++) {
                $weights[] = $this->randomWeight();
            }

            $args[] = 'WEIGHTS';
            $args = array_merge($args, $weights);
        }

        if ($numKeys > 1 && random_int(0, 1) === 1) {
            $args[] = 'AGGREGATE';
            $args[] = $this->randomAggregate();
        }

        if ($withScoresAllowed && random_int(0, 1) === 1) {
            $args[] = 'WITHSCORES';
        }

        return $args;
    }

    private function randomWeight(): string
    {
        if (random_int(0, 1) === 0) {
            return (string) random_int(1, 100);
        }

        $numerator = random_int(1, 100);
        $denominator = random_int(1, 10);

        return (string) ($numerator / $denominator);
    }

    private function randomAggregate(): string
    {
        $options = ['SUM', 'MIN', 'MAX'];

        return $options[random_int(0, count($options) - 1)];
    }
}
