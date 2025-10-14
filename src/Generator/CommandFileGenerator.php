<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Generator;

use Michaelgrunder\RedisClientCompare\Command\Command;
use Michaelgrunder\RedisClientCompare\Command\CommandRegistry;
use RuntimeException;
use SplFileObject;

final class CommandFileGenerator
{
    private const STATE_ATOMIC = 0x00;
    private const STATE_PIPELINE = 0x01;
    private const STATE_MULTI = 0x02;

    /** @var list<string> */
    private const NON_ATOMIC_OPERATIONS = ['PIPELINE', 'MULTI', 'EXEC', 'DISCARD'];

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
        array $commandFilters = []
    ): void
    {
        if ($count <= 0) {
            throw new RuntimeException('Count must be greater than zero.');
        }

        $this->clusterMode = $clusterMode;
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
        $commands = $this->filterCommandsByName($commands, $commandFilters);
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
        $nonAtomic = $this->clusterMode ? 0 : count(self::NON_ATOMIC_OPERATIONS);
        $poolSize = $totalCommands + $nonAtomic;
        $index = random_int(0, $poolSize - 1);

        if ($index < $totalCommands) {
            /** @var Command $command */
            $command = $commands[$index];
            return $this->clusterMode ? $command->buildClusterCommand() : $command->buildCommand();
        }

        if ($this->clusterMode) {
            return null;
        }

        $operation = self::NON_ATOMIC_OPERATIONS[$index - $totalCommands] ?? null;
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
        }

        return null;
    }

    /**
     * @param Command[] $commands
     * @param list<string> $filters
     * @return Command[]
     */
    private function filterCommandsByName(array $commands, array $filters): array
    {
        if ($filters === []) {
            return $commands;
        }

        $normalizedFilters = array_values(array_filter(
            array_map(
                static fn(string $filter): string => strtoupper($filter),
                $filters
            ),
            static fn(string $filter): bool => $filter !== ''
        ));

        if ($normalizedFilters === []) {
            return $commands;
        }

        return array_values(
            array_filter(
                $commands,
                function (Command $command) use ($normalizedFilters): bool {
                    $name = strtoupper($command->getName());
                    foreach ($normalizedFilters as $pattern) {
                        if ($this->nameMatchesPattern($name, $pattern)) {
                            return true;
                        }
                    }

                    return false;
                }
            )
        );
    }

    private function nameMatchesPattern(string $name, string $pattern): bool
    {
        if ($pattern === '') {
            return false;
        }

        if (function_exists('fnmatch')) {
            return fnmatch($pattern, $name);
        }

        $escaped = preg_quote($pattern, '/');
        $escaped = str_replace(['\*', '\?'], ['.*', '.'], $escaped);

        return (bool) preg_match('/^' . $escaped . '$/', $name);
    }
}
