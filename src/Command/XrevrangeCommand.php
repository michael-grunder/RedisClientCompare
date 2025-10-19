<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class XrevrangeCommand extends StreamCommand
{
    protected const NAME = 'XREVRANGE';
    protected const ATTRIBUTES = [
        'data_type' => 'stream',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        $key = $this->randomStreamKey();
        $start = $this->randomReverseStart();
        $end = $this->randomReverseEnd();

        $arguments = [$key, $start, $end];

        if (random_int(0, 1) === 1) {
            $arguments[] = 'COUNT';
            $arguments[] = random_int(1, 50);
        }

        return $arguments;
    }

    private function randomReverseStart(): string
    {
        return match (random_int(0, 3)) {
            0 => '+',
            1 => '9999999999999-999',
            default => $this->randomStreamId(),
        };
    }

    private function randomReverseEnd(): string
    {
        return match (random_int(0, 3)) {
            0 => '-',
            1 => '0-0',
            default => $this->randomStreamId(),
        };
    }
}
