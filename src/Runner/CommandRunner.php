<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Runner;

use Redis;
use RuntimeException;
use SplFileObject;
use Throwable;

final class CommandRunner
{
    private const STATE_ATOMIC = 0x00;
    private const STATE_PIPELINE = 0x01;
    private const STATE_MULTI = 0x02;

    public function __construct(
        private readonly Redis $redis = new Redis(),
        private int $state = self::STATE_ATOMIC
    ) {
    }

    public function run(
        string $commandsFile,
        string $outputFile,
        string $host = '127.0.0.1',
        int $port = 6379,
        bool $aggregateMode = false
    ): void
    {
        if (!extension_loaded('redis')) {
            throw new RuntimeException('The phpredis extension is not loaded.');
        }

        if (!is_readable($commandsFile)) {
            throw new RuntimeException(sprintf('Commands file not readable: %s', $commandsFile));
        }

        $this->connect($host, $port);
        $this->flushAll();
        $this->state = self::STATE_ATOMIC;

        $input = new SplFileObject($commandsFile, 'r');
        $output = new SplFileObject($outputFile, 'w');

        $aggregate = $aggregateMode ? $this->createAggregateState() : null;
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

            $record = [
                'index' => $index,
                'cmd' => $commandName,
                'args' => $data,
                'result' => null,
                'error' => null,
            ];

            try {
                $result = $this->executeCommand($commandName, $data);
                $record['result'] = $this->normalize($result);
                if ($aggregate !== null) {
                    $this->updateAggregate($aggregate, $commandName, $result, null);
                }
            } catch (Throwable $exception) {
                $record['error'] = $exception->getMessage();
                if ($aggregate !== null) {
                    $this->updateAggregate($aggregate, $commandName, null, $record['error']);
                }
            }

            $output->fwrite(json_encode($record) . PHP_EOL);
            $index++;
        }

        if ($aggregate !== null) {
            $output->fwrite(json_encode($aggregate) . PHP_EOL);
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

        if (is_object($value)) {
            return [
                '_type' => 'object',
                'class' => $value::class,
            ];
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

    /**
     * @param array<int, mixed> $args
     */
    private function executeCommand(string $commandName, array $args): mixed
    {
        return match ($commandName) {
            'PIPELINE' => $this->startPipeline(),
            'MULTI' => $this->startMulti(),
            'EXEC' => $this->executeExec(),
            'DISCARD' => $this->executeDiscard(),
            default => $this->redis->rawCommand($commandName, ...$args),
        };
    }

    private function startPipeline(): mixed
    {
        $result = $this->redis->pipeline();
        $this->state |= self::STATE_PIPELINE;

        return $result;
    }

    private function startMulti(): mixed
    {
        $result = $this->redis->multi();
        $this->state |= self::STATE_MULTI;

        return $result;
    }

    private function executeExec(): mixed
    {
        if ($this->state === self::STATE_ATOMIC) {
            return false;
        }

        $result = $this->redis->exec();

        if (($this->state & self::STATE_MULTI) !== 0) {
            $this->state &= ~self::STATE_MULTI;
        } elseif (($this->state & self::STATE_PIPELINE) !== 0) {
            $this->state &= ~self::STATE_PIPELINE;
        }

        return $result;
    }

    private function executeDiscard(): mixed
    {
        if ($this->state === self::STATE_ATOMIC) {
            return false;
        }

        $result = $this->redis->discard();

        $this->state = self::STATE_ATOMIC;

        return $result;
    }

    private function createAggregateState(): array
    {
        return [
            'type' => 'aggregate',
            'commands' => [],
            'meta' => [
                'total_string_length' => 0,
                'total_array_length' => 0,
            ],
        ];
    }

    private function updateAggregate(array &$aggregate, string $commandName, mixed $result, ?string $error): void
    {
        if (!isset($aggregate['commands'][$commandName])) {
            $aggregate['commands'][$commandName] = [
                'meta' => [
                    'total_string_length' => 0,
                    'total_array_length' => 0,
                ],
            ];
        }

        if ($error !== null) {
            $this->incrementAggregateBucket($aggregate['commands'][$commandName], 'error');
            return;
        }

        $bucket = $this->resolveAggregateBucket($result);
        $this->incrementAggregateBucket($aggregate['commands'][$commandName], $bucket);

        if ($bucket === 'string') {
            $length = strlen((string) $result);
            $aggregate['meta']['total_string_length'] += $length;
            $aggregate['commands'][$commandName]['meta']['total_string_length'] += $length;

            return;
        }

        if ($bucket === 'array') {
            $length = is_countable($result) ? count($result) : 0;
            $aggregate['meta']['total_array_length'] += $length;
            $aggregate['commands'][$commandName]['meta']['total_array_length'] += $length;
        }
    }

    private function incrementAggregateBucket(array &$commandAggregate, string $bucket): void
    {
        if (!isset($commandAggregate[$bucket])) {
            $commandAggregate[$bucket] = 0;
        }

        $commandAggregate[$bucket]++;
    }

    private function resolveAggregateBucket(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_int($value)) {
            return 'int';
        }

        if (is_float($value)) {
            return 'float';
        }

        if (is_string($value)) {
            return 'string';
        }

        if (is_array($value)) {
            return 'array';
        }

        if (is_object($value)) {
            return 'object';
        }

        return 'other';
    }
}
