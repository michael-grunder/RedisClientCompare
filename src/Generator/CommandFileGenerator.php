<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Generator;

use Michaelgrunder\RedisClientCompare\Command\Command;
use Michaelgrunder\RedisClientCompare\Command\CommandFilter;
use Michaelgrunder\RedisClientCompare\Command\CommandRegistry;
use Michaelgrunder\RedisClientCompare\PhpRedis\Capabilities;
use Redis;
use RuntimeException;
use SplFileObject;

final class CommandFileGenerator
{
    private const STATE_ATOMIC = 0x00;
    private const STATE_PIPELINE = 0x01;
    private const STATE_MULTI = 0x02;

    /** @var list<string> */
    private const NON_ATOMIC_OPERATIONS = ['PIPELINE', 'MULTI', 'EXEC', 'DISCARD'];
    private bool $includeSetOptions = false;

    public function __construct(
        private readonly CommandRegistry $registry = new CommandRegistry(),
        private bool $clusterMode = false
    ) {
    }

    /**
     * @param list<string> $commandFilters
     */
    public function generate(
        int $count,
        string $outputPath,
        int $keyCardinality = Command::DEFAULT_KEY_CARDINALITY,
        int $memberCardinality = Command::DEFAULT_MEMBER_CARDINALITY,
        bool $includeExpirationCommands = false,
        bool $clusterMode = false,
        array $commandFilters = [],
        bool $includeSetOptions = false
    ): void
    {
        if ($count <= 0) {
            throw new RuntimeException('Count must be greater than zero.');
        }

        $this->clusterMode = $clusterMode;
        $this->includeSetOptions = $includeSetOptions;
        Command::configureGenerator($keyCardinality, $memberCardinality);

        $commands = $this->registry->createInstances();
        if (!$includeExpirationCommands) {
            $commands = array_values(
                array_filter(
                    $commands,
                    static fn (Command $command): bool => !$command->interactsWithExpiration()
                )
            );
        }
        $commands = CommandFilter::apply($commands, $commandFilters);
        if ($commands === []) {
            throw new RuntimeException('No command classes available after applying filters.');
        }

        $file = new SplFileObject($outputPath, 'w');
        $state = self::STATE_ATOMIC;

        $generated = 0;
        while ($generated < $count) {
            $entry = $this->nextEntry($commands, $state);
            if ($entry === null) {
                continue;
            }

            $file->fwrite(json_encode($entry) . PHP_EOL);
            $generated++;
        }

        $this->flushNonAtomicState($file, $state);
    }

    /**
     * @param Command[] $commands
     * @return array<int, mixed>|null
     */
    private function nextEntry(array $commands, int &$state): ?array
    {
        $totalCommands = count($commands);
        $metaOperations = $this->clusterMode ? [] : self::NON_ATOMIC_OPERATIONS;

        if (!$this->clusterMode && $this->includeSetOptions) {
            $metaOperations[] = 'SETOPTION';
        }

        $poolSize = $totalCommands + count($metaOperations);
        $index = random_int(0, $poolSize - 1);

        if ($index < $totalCommands) {
            /** @var Command $command */
            $command = $commands[$index];
            return $this->clusterMode ? $command->buildClusterCommand() : $command->buildCommand();
        }

        if ($this->clusterMode) {
            return null;
        }

        $operation = $metaOperations[$index - $totalCommands] ?? null;
        if ($operation === null) {
            return null;
        }

        return $this->performNonAtomicOperation($operation, $state);
    }

    private function flushNonAtomicState(SplFileObject $file, int &$state): void
    {
        if ($this->clusterMode) {
            $state = self::STATE_ATOMIC;
            return;
        }

        while ($state !== self::STATE_ATOMIC) {
            $entry = $this->performNonAtomicOperation('EXEC', $state);
            if ($entry === null) {
                break;
            }

            $file->fwrite(json_encode($entry) . PHP_EOL);
        }
    }

    /**
     * @return array<int, mixed>|null
     */
    private function performNonAtomicOperation(string $operation, int &$state): ?array
    {
        switch ($operation) {
            case 'PIPELINE':
                if (($state & self::STATE_PIPELINE) !== 0 || ($state & self::STATE_MULTI) !== 0) {
                    return null;
                }

                $state |= self::STATE_PIPELINE;
                return [$operation];

            case 'MULTI':
                if (($state & self::STATE_MULTI) !== 0 || ($state & self::STATE_PIPELINE) !== 0) {
                    return null;
                }

                $state |= self::STATE_MULTI;
                return [$operation];

            case 'EXEC':
                if ($state === self::STATE_ATOMIC) {
                    return null;
                }

                if (($state & self::STATE_MULTI) !== 0) {
                    $state &= ~self::STATE_MULTI;
                } elseif (($state & self::STATE_PIPELINE) !== 0) {
                    $state &= ~self::STATE_PIPELINE;
                }

                return [$operation];

            case 'DISCARD':
                if (($state & self::STATE_MULTI) !== 0) {
                    $state &= ~self::STATE_MULTI;
                } elseif (($state & self::STATE_PIPELINE) !== 0) {
                    $state &= ~self::STATE_PIPELINE;
                }

                return [$operation];

            case 'SETOPTION':
                if ($state !== self::STATE_ATOMIC) {
                    return null;
                }

                return $this->generateSetOptionEntry();
        }

        return null;
    }

    /**
     * @return array<int, mixed>|null
     */
    private function generateSetOptionEntry(): ?array
    {
        $options = [];

        if (\defined('Redis::OPT_PREFIX')) {
            $prefixValues = $this->generatePrefixValues();
            if ($prefixValues !== []) {
                $options[] = [
                    'option' => (int) Redis::OPT_PREFIX,
                    'values' => $prefixValues,
                ];
            }
        }

        if (\defined('Redis::OPT_COMPRESSION')) {
            $compressors = Capabilities::availableCompressors();
            if ($compressors !== []) {
                $options[] = [
                    'option' => (int) Redis::OPT_COMPRESSION,
                    'values' => $compressors,
                ];
            }
        }

        if (\defined('Redis::OPT_SERIALIZER')) {
            $serializers = Capabilities::availableSerializers();
            if ($serializers !== []) {
                $options[] = [
                    'option' => (int) Redis::OPT_SERIALIZER,
                    'values' => $serializers,
                ];
            }
        }

        if ($options === []) {
            return null;
        }

        $choice = $options[random_int(0, count($options) - 1)];
        $values = $choice['values'];
        if ($values === []) {
            return null;
        }

        $value = $values[random_int(0, count($values) - 1)];

        $optionValue = (int) $choice['option'];
        $optionName = $this->resolveRedisConstantName($optionValue, 'Redis::OPT_');

        if (is_int($value)) {
            $valuePrefix = null;
            if ($optionValue === Redis::OPT_SERIALIZER) {
                $valuePrefix = 'Redis::SERIALIZER_';
            } elseif ($optionValue === Redis::OPT_COMPRESSION) {
                $valuePrefix = 'Redis::COMPRESSION_';
            }

            $valueName = $this->resolveRedisConstantName($value, $valuePrefix);
            if ($valueName !== null) {
                $value = $valueName;
            }
        }

        return [
            'SETOPTION',
            $optionName ?? $optionValue,
            $value,
        ];
    }

    /**
     * @return list<string>
     */
    private function generatePrefixValues(): array
    {
        $values = [
            '',
            'prefix:',
            'ns::',
        ];

        $dynamicCount = random_int(1, 4);
        for ($i = 0; $i < $dynamicCount; $i++) {
            $values[] = $this->randomAscii(1, 5) . ':';
            $values[] = $this->randomAscii(1, 8);
        }

        $values[] = strtoupper($this->randomAscii(1, 3)) . '_';

        return array_values(array_unique($values));
    }

    private function randomAscii(int $min, int $max): string
    {
        $length = max($min, random_int($min, $max));
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-:.';
        $alphabetLength = strlen($alphabet) - 1;
        $value = '';

        for ($i = 0; $i < $length; $i++) {
            $value .= $alphabet[random_int(0, $alphabetLength)];
        }

        return $value;
    }

    /**
     * @param int|string $value
     */
    private function resolveRedisConstantName(int|string $value, ?string $requiredPrefix = null): ?string
    {
        static $constantMap = null;

        if ($constantMap === null) {
            $reflection = new \ReflectionClass(Redis::class);
            $className = $reflection->getShortName();
            $map = [];

            foreach ($reflection->getConstants() as $name => $constValue) {
                if (!is_int($constValue) && !is_string($constValue)) {
                    continue;
                }

                $key = $this->constantKey($constValue);
                $map[$key][] = sprintf('%s::%s', $className, $name);
            }

            $constantMap = $map;
        }

        $names = $constantMap[$this->constantKey($value)] ?? null;
        if ($names === null) {
            return null;
        }

        if ($requiredPrefix !== null) {
            foreach ($names as $candidate) {
                if (str_starts_with($candidate, $requiredPrefix)) {
                    return $candidate;
                }
            }

            return null;
        }

        return $names[0] ?? null;
    }

    /**
     * @param int|string $value
     */
    private function constantKey(int|string $value): string
    {
        return gettype($value) . ':' . (string) $value;
    }
}
