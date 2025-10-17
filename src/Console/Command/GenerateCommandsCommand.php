<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Console\Command;

use Michaelgrunder\RedisClientCompare\Command\Command;
use Michaelgrunder\RedisClientCompare\Generator\CommandFileGenerator;
use Michaelgrunder\RedisClientCompare\Logger\ExceptionFormatter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'commands:generate',
    description: 'Generate a JSONL file containing Redis commands.'
)]
final class GenerateCommandsCommand extends SymfonyCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('count', InputArgument::REQUIRED, 'Number of commands to generate.')
            ->addArgument('output', InputArgument::REQUIRED, 'Path to the JSONL output file.')
            ->addOption(
                'keys',
                null,
                InputOption::VALUE_REQUIRED,
                'Maximum number of keys to use when generating commands.',
                Command::DEFAULT_KEY_CARDINALITY
            )
            ->addOption(
                'members',
                null,
                InputOption::VALUE_REQUIRED,
                'Maximum number of members when generating set-like commands.',
                Command::DEFAULT_MEMBER_CARDINALITY
            )
            ->addOption(
                'include-expiration',
                null,
                InputOption::VALUE_NONE,
                'Include commands that interact with expiration metadata.'
            )
            ->addOption(
                'cluster',
                null,
                InputOption::VALUE_NONE,
                'Generate commands suitable for cluster mode.'
            )
            ->addOption(
                'commands',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Only generate commands matching the supplied patterns (comma separated).'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = (int) $input->getArgument('count');
        $outputFile = (string) $input->getArgument('output');
        $keyCardinality = (int) $input->getOption('keys');
        $memberCardinality = (int) $input->getOption('members');
        $includeExpiration = (bool) $input->getOption('include-expiration');
        $cluster = (bool) $input->getOption('cluster');

        /** @var list<string> $commandFilters */
        $commandFilters = $this->normalizeCommandFilters($input->getOption('commands'));

        $io = new SymfonyStyle($input, $output);

        if ($count < 1) {
            $io->error('Count must be >= 1.');
            return SymfonyCommand::INVALID;
        }

        if ($keyCardinality < 1) {
            $io->error('--keys must be >= 1.');
            return SymfonyCommand::INVALID;
        }

        if ($memberCardinality < 1) {
            $io->error('--members must be >= 1.');
            return SymfonyCommand::INVALID;
        }

        try {
            (new CommandFileGenerator())->generate(
                $count,
                $outputFile,
                $keyCardinality,
                $memberCardinality,
                $includeExpiration,
                $cluster,
                $commandFilters
            );
        } catch (\Throwable $exception) {
            $io->error(ExceptionFormatter::format($exception));
            return SymfonyCommand::FAILURE;
        }

        $io->success(sprintf(
            'Generated %d command%s at %s',
            $count,
            $count === 1 ? '' : 's',
            $outputFile
        ));

        return SymfonyCommand::SUCCESS;
    }

    /**
     * @param mixed $raw
     * @return list<string>
     */
    private function normalizeCommandFilters(mixed $raw): array
    {
        if ($raw === null || $raw === []) {
            return [];
        }

        if (!is_array($raw)) {
            $raw = [$raw];
        }

        $filters = [];
        foreach ($raw as $value) {
            if (!is_string($value)) {
                continue;
            }

            foreach (explode(',', $value) as $fragment) {
                $candidate = trim($fragment);
                if ($candidate === '') {
                    continue;
                }

                $candidate = trim($candidate, " \t\n\r\0\x0B'\"");
                if ($candidate === '') {
                    continue;
                }

                $filters[strtoupper($candidate)] = $candidate;
            }
        }

        return array_values($filters);
    }
}
