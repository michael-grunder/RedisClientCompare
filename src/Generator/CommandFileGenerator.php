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
        private readonly CommandRegistry $registry = new CommandRegistry()
    ) {
    }

    public function generate(
        int $count,
        string $outputPath,
        int $keyCardinality = Command::DEFAULT_KEY_CARDINALITY,
        int $memberCardinality = Command::DEFAULT_MEMBER_CARDINALITY
    ): void
    {
        if ($count <= 0) {
            throw new RuntimeException('Count must be greater than zero.');
        }

        Command::configureGenerator($keyCardinality, $memberCardinality);

        $commands = $this->registry->createInstances();
        if ($commands === []) {
            throw new RuntimeException('No command classes were discovered.');
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
        $poolSize = $totalCommands + count(self::NON_ATOMIC_OPERATIONS);
        $index = random_int(0, $poolSize - 1);

        if ($index < $totalCommands) {
            /** @var Command $command */
            $command = $commands[$index];
            return $command->buildCommand();
        }

        $operation = self::NON_ATOMIC_OPERATIONS[$index - $totalCommands] ?? null;
        if ($operation === null) {
            return null;
        }

        return $this->performNonAtomicOperation($operation, $state);
    }

    private function flushNonAtomicState(SplFileObject $file, int &$state): void
    {
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
                if (($state & self::STATE_MULTI) !== 0) {
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
}
