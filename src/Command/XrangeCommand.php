<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class XrangeCommand extends StreamCommand
{
    protected const NAME = 'XRANGE';
    protected const ATTRIBUTES = [
        'data_type' => 'stream',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        $key = $this->randomStreamKey();
        $start = $this->randomRangeStart();
        $end = $this->randomRangeEnd();

        $arguments = [$key, $start, $end];

        if (random_int(0, 1) === 1) {
            $arguments[] = 'COUNT';
            $arguments[] = random_int(1, 50);
        }

        return $arguments;
    }

    private function randomRangeStart(): string
    {
        return match (random_int(0, 3)) {
            0 => '-',
            1 => '0-0',
            default => $this->randomStreamId(),
        };
    }

    private function randomRangeEnd(): string
    {
        return match (random_int(0, 3)) {
            0 => '+',
            1 => $this->randomStreamId(),
            default => '9999999999999-999',
        };
    }
}
