<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Runner;

use Redis;
use RuntimeException;
use SplFileObject;
use Throwable;

final class CommandRunner
{
    public function __construct(
        private readonly Redis $redis = new Redis()
    ) {
    }

    public function run(string $commandsFile, string $outputFile, string $host = '127.0.0.1', int $port = 6379): void
    {
        if (!extension_loaded('redis')) {
            throw new RuntimeException('The phpredis extension is not loaded.');
        }

        if (!is_readable($commandsFile)) {
            throw new RuntimeException(sprintf('Commands file not readable: %s', $commandsFile));
        }

        $this->connect($host, $port);
        $this->flushAll();

        $input = new SplFileObject($commandsFile, 'r');
        $output = new SplFileObject($outputFile, 'w');

        $meta = [
            'type' => 'meta',
            'time' => date('c'),
            'host' => $host,
            'port' => $port,
            'php_version' => phpversion(),
            'redis_ext_version' => phpversion('redis') ?: 'unknown',
            'sapi' => php_sapi_name(),
        ];
        $output->fwrite(json_encode($meta) . PHP_EOL);

        $index = 0;
        while (!$input->eof()) {
            $line = trim($input->fgets());
            if ($line === '') {
                continue;
            }

            $data = json_decode($line, true);
            if (!is_array($data) || $data === []) {
                continue;
            }

            $commandName = array_shift($data);
            $call = array_merge([$commandName], $data);

            $record = [
                'index' => $index,
                'cmd' => $commandName,
                'args' => $data,
                'result' => null,
                'error' => null,
            ];

            try {
                $result = $this->redis->rawCommand(...$call);
                $record['result'] = $this->normalize($result);
            } catch (Throwable $exception) {
                $record['error'] = $exception->getMessage();
            }

            $output->fwrite(json_encode($record) . PHP_EOL);
            $index++;
        }
    }

    private function connect(string $host, int $port): void
    {
        try {
            $this->redis->connect($host, $port);
        } catch (Throwable $exception) {
            throw new RuntimeException(sprintf('Failed to connect to Redis: %s', $exception->getMessage()), 0, $exception);
        }
    }

    private function flushAll(): void
    {
        try {
            $this->redis->flushAll();
        } catch (Throwable) {
            // Some servers restrict FLUSHALL; ignore errors.
        }
    }

    private function normalize(mixed $value): mixed
    {
        if (is_bool($value)) {
            return ['_type' => 'bool', 'v' => $value];
        }

        if ($value === null || is_int($value) || is_float($value) || is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $child) {
                $normalized[$key] = $this->normalize($child);
            }

            return $normalized;
        }

        return ['_type' => 'other', 'repr' => (string) $value];
    }
}
