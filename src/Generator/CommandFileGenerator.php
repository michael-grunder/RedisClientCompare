<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Generator;

use Michaelgrunder\RedisClientCompare\Command\Command;
use Michaelgrunder\RedisClientCompare\Command\CommandRegistry;
use RuntimeException;
use SplFileObject;

final class CommandFileGenerator
{
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

        $maxIndex = count($commands) - 1;
        for ($i = 0; $i < $count; $i++) {
            /** @var Command $command */
            $command = $commands[random_int(0, $maxIndex)];
            $file->fwrite(json_encode($command->buildCommand()) . PHP_EOL);
        }
    }
}
