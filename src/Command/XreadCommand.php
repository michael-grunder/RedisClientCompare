<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class XreadCommand extends StreamCommand
{
    protected const NAME = 'XREAD';
    protected const ATTRIBUTES = [
        'data_type' => 'stream',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        $streamCount = 1;
        $keys = [];
        $ids = [];

        for ($i = 0; $i < $streamCount; $i++) {
            $keys[] = $this->randomStreamKey();
            $ids[] = $this->randomReadId();
        }

        $arguments = [];

        if (random_int(0, 1) === 1) {
            $arguments[] = 'COUNT';
            $arguments[] = random_int(1, 50);
        }

        $arguments[] = 'STREAMS';

        foreach ($keys as $key) {
            $arguments[] = $key;
        }

        foreach ($ids as $id) {
            $arguments[] = $id;
        }

        return $arguments;
    }

    private function randomReadId(): string
    {
        return match (random_int(0, 2)) {
            0 => '0-0',
            1 => sprintf('%d-%d', random_int(0, 1_000_000), random_int(0, 50)),
            default => $this->randomStreamId(),
        };
    }
}
