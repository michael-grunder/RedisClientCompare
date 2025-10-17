<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Console\Command;

use Michaelgrunder\RedisClientCompare\Comparator\CommandComparator;
use Michaelgrunder\RedisClientCompare\Logger\ExceptionFormatter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'outputs:compare',
    description: 'Compare two Redis command execution outputs.'
)]
final class CompareOutputsCommand extends SymfonyCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('first', InputArgument::REQUIRED, 'Path to the first output JSONL file.')
            ->addArgument('second', InputArgument::REQUIRED, 'Path to the second output JSONL file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $first = (string) $input->getArgument('first');
        $second = (string) $input->getArgument('second');

        try {
            $result = (new CommandComparator())->compare($first, $second);
        } catch (\Throwable $exception) {
            $io->error(ExceptionFormatter::format($exception));
            return SymfonyCommand::FAILURE;
        }

        if ($result->isIdentical()) {
            $io->success('Outputs are identical.');
            return SymfonyCommand::SUCCESS;
        }

        $index = $result->getIndex() ?? -1;
        $message = $result->getMessage() ?? 'unknown';

        if ($message === 'DIFFERENT LENGTH') {
            $io->error(sprintf('Outputs differ: different length at command index %d.', $index));
        } else {
            $io->error(sprintf('Outputs differ at command index %d: %s', $index, $message));
        }

        $recordA = $result->getRecordA();
        $recordB = $result->getRecordB();

        if ($recordA !== null) {
            $io->writeln('--- run A ---');
            $io->writeln(json_encode($recordA, JSON_PRETTY_PRINT) ?: '{}');
        }

        if ($recordB !== null) {
            $io->writeln('--- run B ---');
            $io->writeln(json_encode($recordB, JSON_PRETTY_PRINT) ?: '{}');
        }

        return SymfonyCommand::FAILURE;
    }
}
