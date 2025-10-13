<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Runner;

use Redis;
use RedisCluster;
use RuntimeException;
use SplFileObject;
use Throwable;

final class CommandRunner
{
    private const STATE_ATOMIC = 0x00;
    private const STATE_PIPELINE = 0x01;
    private const STATE_MULTI = 0x02;

    private Redis|RedisCluster|null $client = null;
    private int $state = self::STATE_ATOMIC;
    private bool $clusterMode = false;

    public function run(
        string $commandsFile,
        string $outputFile,
        string $host = '127.0.0.1',
        int $port = 6379,
        bool $aggregateMode = false,
        bool $clusterMode = false
    ): void
    {
        if (!extension_loaded('redis')) {
            throw new RuntimeException('The phpredis extension is not loaded.');
        }

        if (!is_readable($commandsFile)) {
            throw new RuntimeException(sprintf('Commands file not readable: %s', $commandsFile));
        }

        $this->clusterMode = $clusterMode;
        $this->client = null;
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
            'cluster' => $this->clusterMode,
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

    /**
     * @param array<int|string, mixed> $node
     * @return array{0: string, 1: int}
     */
    private function normalizeNodeTarget(array $node): array
    {
        if (isset($node['ip'], $node['port'])) {
            return [(string) $node['ip'], (int) $node['port']];
        }

        if (isset($node['addr'])) {
            $parts = explode(':', (string) $node['addr'], 2);
            $host = $parts[0] ?? '127.0.0.1';
            $port = isset($parts[1]) ? (int) $parts[1] : 0;

            return [$host, $port];
        }

        if (isset($node[0], $node[1])) {
            return [(string) $node[0], (int) $node[1]];
        }

        if (isset($node['host'], $node['port'])) {
            return [(string) $node['host'], (int) $node['port']];
        }

        return ['127.0.0.1', 0];
    }

    private function connect(string $host, int $port): void
    {
        try {
            if ($this->clusterMode) {
                $seed = sprintf('%s:%d', $host, $port);
                $this->client = new RedisCluster(null, [$seed]);
            } else {
                $client = new Redis();
                $client->connect($host, $port);
                $this->client = $client;
            }
        } catch (Throwable $exception) {
            throw new RuntimeException(sprintf('Failed to connect to Redis: %s', $exception->getMessage()), 0, $exception);
        }

        if ($this->client === null) {
            throw new RuntimeException('Failed to initialize Redis client.');
        }
    }

    private function flushAll(): void
    {
        try {
            if ($this->clusterMode && $this->client instanceof RedisCluster) {
                $masters = $this->client->masters();
                foreach ($masters as $master) {
                    if (!is_array($master)) {
                        continue;
                    }

                    $node = $this->normalizeNodeTarget($master);
                    if ($node[0] === '' || $node[1] <= 0) {
                        continue;
                    }

                    try {
                        $this->client->rawCommand($node, 'FLUSHALL');
                    } catch (Throwable) {
                        // Ignore failures on individual nodes.
                    }
                }

                return;
            }

            if ($this->client instanceof Redis) {
                $this->client->flushAll();
            }
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
        $normalizedCommand = strtoupper($commandName);

        if ($this->clusterMode) {
            return $this->executeClusterCommand($normalizedCommand, $commandName, $args);
        }

        if (!$this->client instanceof Redis) {
            throw new RuntimeException('Missing Redis client for command execution.');
        }

        return match ($normalizedCommand) {
            'PIPELINE' => $this->startPipeline(),
            'MULTI' => $this->startMulti(),
            'EXEC' => $this->executeExec(),
            'DISCARD' => $this->executeDiscard(),
            default => $this->client->rawCommand($commandName, ...$args),
        };
    }

    /**
     * @param array<int, mixed> $args
     */
    private function executeClusterCommand(string $normalizedCommand, string $commandName, array $args): mixed
    {
        if (!$this->client instanceof RedisCluster) {
            throw new RuntimeException('Missing RedisCluster client for command execution.');
        }

        if (in_array($normalizedCommand, ['PIPELINE', 'MULTI', 'EXEC', 'DISCARD'], true)) {
            return null;
        }

        if ($args === []) {
            throw new RuntimeException(sprintf('Cluster command %s requires at least one argument.', $commandName));
        }

        $route = $args[0];

        return $this->client->rawCommand($route, $commandName, ...$args);
    }

    private function startPipeline(): mixed
    {
        if (!$this->client instanceof Redis) {
            return null;
        }

        if (($this->state & self::STATE_MULTI) !== 0) {
            // Skip pipeline activation when MULTI is active; phpredis forbids it.
            return $this->client;
        }

        if (($this->state & self::STATE_PIPELINE) !== 0) {
            return $this->client;
        }

        $result = $this->client->pipeline();
        $this->state |= self::STATE_PIPELINE;

        return $result;
    }

    private function startMulti(): mixed
    {
        if (!$this->client instanceof Redis) {
            return null;
        }

        $result = $this->client->multi();
        $this->state |= self::STATE_MULTI;

        return $result;
    }

    private function executeExec(): mixed
    {
        if ($this->state === self::STATE_ATOMIC) {
            return false;
        }

        if (!$this->client instanceof Redis) {
            return null;
        }

        $result = $this->client->exec();

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

        if (!$this->client instanceof Redis) {
            return null;
        }

        $result = $this->client->discard();

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
