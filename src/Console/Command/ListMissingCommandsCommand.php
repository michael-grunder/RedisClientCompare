<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Console\Command;

use Michaelgrunder\RedisClientCompare\Command\CommandRegistry;
use Redis;
use RedisException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'commands:missing',
    description: 'Show Redis commands supported by the server but not implemented in src/Command.'
)]
final class ListMissingCommandsCommand extends SymfonyCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('host', InputArgument::OPTIONAL, 'Redis host to target.', '127.0.0.1')
            ->addArgument('port', InputArgument::OPTIONAL, 'Redis port to target.', 6379);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $host = (string) $input->getArgument('host');
        $port = (int) $input->getArgument('port');

        if ($port < 1) {
            $io->error('The port must be a positive integer.');
            return SymfonyCommand::INVALID;
        }

        $redis = new Redis();

        try {
            $redis->connect($host, $port);
        } catch (RedisException $exception) {
            $io->error(sprintf(
                'Failed to connect to Redis at %s:%d: %s',
                $host,
                $port,
                $exception->getMessage()
            ));

            return SymfonyCommand::FAILURE;
        }

        $info = $redis->info();
        $version = 'unknown';
        if (is_array($info)) {
            $version = $info['redis_version']
                ?? ($info['Server']['redis_version'] ?? 'unknown');
        }

        $commandResponse = $redis->command();
        if (!is_array($commandResponse)) {
            $io->error('Redis returned an unexpected response to the COMMAND command.');
            return SymfonyCommand::FAILURE;
        }

        $redisCommands = [];
        foreach ($commandResponse as $command) {
            if (isset($command[0]) && is_string($command[0])) {
                $redisCommands[] = strtoupper($command[0]);
            }
        }

        $redisCommands = array_values(array_unique($redisCommands));
        sort($redisCommands, SORT_STRING);

        $registry = new CommandRegistry();
        $implementedCommands = [];
        foreach ($registry->createInstances() as $instance) {
            $name = strtoupper($instance->getName());
            if ($name !== '') {
                $implementedCommands[] = $name;
            }
        }

        $implementedCommands = array_values(array_unique($implementedCommands));
        sort($implementedCommands, SORT_STRING);

        $missing = array_values(array_diff($redisCommands, $implementedCommands));

        $io->writeln(sprintf('Redis version: %s', $version));

        if (count($missing) === 0) {
            $io->success('All Redis commands are implemented in src/Command.');
            return SymfonyCommand::SUCCESS;
        }

        $io->writeln('');
        $io->warning(sprintf('%d command(s) missing implementation:', count($missing)));
        foreach ($missing as $commandName) {
            $io->writeln($commandName);
        }

        return SymfonyCommand::FAILURE;
    }
}
