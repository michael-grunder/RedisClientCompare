<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Console\Command;

use Michaelgrunder\RedisClientCompare\Command\Command;
use Michaelgrunder\RedisClientCompare\Command\CommandFilter;
use Michaelgrunder\RedisClientCompare\Command\CommandRegistry;
use Michaelgrunder\RedisClientCompare\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'compare:loop',
    description: 'Continuously compare two phpredis builds by generating, running, and diffing command batches.'
)]
final class CompareLoopCommand extends SymfonyCommand
{
    private const EXIT_IDENTICAL_VERSION = 2;
    private const EXIT_GEN_COMMANDS_FAILED = 3;
    private const EXIT_RUN_COMMANDS_OLD_FAILED = 4;
    private const EXIT_RUN_COMMANDS_NEW_FAILED = 5;

    private ?LoggerInterface $logger = null;
    private ?SymfonyStyle $io = null;

    protected function configure(): void
    {
        $this
            ->addOption('old', null, InputOption::VALUE_REQUIRED, 'Path to the PHP binary using the old phpredis build.')
            ->addOption('new', null, InputOption::VALUE_REQUIRED, 'Path to the PHP binary using the new phpredis build.')
            ->addOption('iters', null, InputOption::VALUE_REQUIRED, 'Number of iterations to run (0 for infinite).', 0)
            ->addOption('sleep', null, InputOption::VALUE_REQUIRED, 'Seconds to sleep between iterations.', 0)
            ->addOption('count', null, InputOption::VALUE_REQUIRED, 'Number of commands to generate per iteration.', 200)
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Redis host to target.', '127.0.0.1')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'Redis port to target.', 6379)
            ->addOption('loglevel', null, InputOption::VALUE_REQUIRED, 'Log level for loop telemetry.', 'info')
            ->addOption('keys', null, InputOption::VALUE_REQUIRED, 'Maximum number of keys for generated commands.', Command::DEFAULT_KEY_CARDINALITY)
            ->addOption('members', null, InputOption::VALUE_REQUIRED, 'Maximum number of members for generated commands.', Command::DEFAULT_MEMBER_CARDINALITY)
            ->addOption('cluster', null, InputOption::VALUE_NONE, 'Enable Redis cluster mode.')
            ->addOption(
                'commands',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Only run commands matching the supplied pattern(s).'
            )
            ->addOption(
                'always-false-warning-interval',
                null,
                InputOption::VALUE_REQUIRED,
                'Log frequency (in iterations) for commands that only return false.',
                25
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $phpOld = (string) $input->getOption('old');
        $phpNew = (string) $input->getOption('new');
        $iterations = (int) $input->getOption('iters');
        $sleepSeconds = (int) $input->getOption('sleep');
        $commandCount = (int) $input->getOption('count');
        $host = (string) $input->getOption('host');
        $port = (int) $input->getOption('port');
        $logLevel = $this->normalizeLogLevel((string) $input->getOption('loglevel'));
        $keys = (int) $input->getOption('keys');
        $members = (int) $input->getOption('members');
        $clusterMode = (bool) $input->getOption('cluster');
        $warningInterval = (int) $input->getOption('always-false-warning-interval');

        if ($phpOld === '' || $phpNew === '') {
            $this->io->error('Both --old and --new must be provided.');
            return SymfonyCommand::INVALID;
        }

        if ($commandCount < 1) {
            $this->io->error('--count must be >= 1.');
            return SymfonyCommand::INVALID;
        }

        if ($keys < 1) {
            $this->io->error('--keys must be >= 1.');
            return SymfonyCommand::INVALID;
        }

        if ($members < 1) {
            $this->io->error('--members must be >= 1.');
            return SymfonyCommand::INVALID;
        }

        if ($port < 1) {
            $this->io->error('--port must be >= 1.');
            return SymfonyCommand::INVALID;
        }

        if ($warningInterval < 0) {
            $this->io->error('--always-false-warning-interval must be >= 0.');
            return SymfonyCommand::INVALID;
        }

        $rawCommandFilters = $input->getOption('commands');
        /** @var list<string> $commandFilters */
        $commandFilters = $this->normalizeCommandFilters($rawCommandFilters);

        if ($commandFilters === [] && $input->hasParameterOption('--commands')) {
            $this->io->error('--commands requires at least one non-empty pattern.');
            return SymfonyCommand::INVALID;
        }

        try {
            $this->logger = LoggerFactory::create($logLevel);
        } catch (\Throwable $exception) {
            $this->io->error(sprintf("Invalid log level '%s'.", $logLevel));
            $this->io->error($exception->getMessage());
            return SymfonyCommand::INVALID;
        }

        $resolvedCommandNames = [];
        $resolvedCommandNames = [];
        if ($commandFilters !== []) {
            $resolvedCommandNames = $this->resolveFilteredCommandNames($commandFilters);
            if ($resolvedCommandNames === []) {
                $this->logError('Command filters excluded every command', ['command_filters' => $commandFilters]);
                return SymfonyCommand::INVALID;
            }

            $this->logInfo('Command filters applied', [
                'command_filters' => $commandFilters,
                'commands' => $resolvedCommandNames,
            ]);
        }

        $exitCode = $this->runLoop(
            $phpOld,
            $phpNew,
            $iterations,
            $sleepSeconds,
            $commandCount,
            $host,
            $port,
            $keys,
            $members,
            $clusterMode,
            $commandFilters,
            $warningInterval,
            $resolvedCommandNames
        );

        return $exitCode;
    }

    /**
     * @param list<string> $commandFilters
     */
    private function runLoop(
        string $phpOld,
        string $phpNew,
        int $iterations,
        int $sleepSeconds,
        int $commandCount,
        string $host,
        int $port,
        int $keys,
        int $members,
        bool $clusterMode,
        array $commandFilters,
        int $warningInterval,
        array $resolvedCommandNames
    ): int {
        $binDir = \dirname(__DIR__, 3) . '/bin';
        $generator = $binDir . '/gen-commands';
        $runner = $binDir . '/run-commands';
        $comparator = $binDir . '/compare-outputs';

        $oldVersion = $this->getRedisVersion($phpOld);
        $newVersion = $this->getRedisVersion($phpNew);

        if (
            $oldVersion !== '' &&
            $newVersion !== '' &&
            \strtolower($oldVersion) === \strtolower($newVersion)
        ) {
            $this->logError('Identical phpredis versions detected', [
                'php_old' => $phpOld,
                'php_new' => $phpNew,
                'phpredis_version' => $oldVersion,
            ]);
            $this->logSummary('identical-phpredis-version', 0, 0, \microtime(true));

            return self::EXIT_IDENTICAL_VERSION;
        }

        foreach ([$generator, $runner, $comparator] as $binary) {
            if (!\is_file($binary)) {
                $this->logError('Missing binary', ['path' => $binary]);
                $this->logSummary('missing-binary', 0, 0, \microtime(true));
                return SymfonyCommand::FAILURE;
            }
        }

        $workDir = $this->buildWorkDir();
        $cleanupWorkDir = true;

        $cleanup = static function () use (&$cleanupWorkDir, $workDir): void {
            if (!$cleanupWorkDir) {
                return;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($workDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $file) {
                $filename = $file->getPathname();
                $file->isDir() ? @\rmdir($filename) : @\unlink($filename);
            }

            @\rmdir($workDir);
        };

        try {
            $this->logInfo(
                'Loop start',
                [
                    'old' => $phpOld,
                    'new' => $phpNew,
                    'redis' => sprintf('%s->%s', $oldVersion ?: 'N/A', $newVersion ?: 'N/A'),
                    'batch' => number_format($commandCount),
                    'iters' => $iterations > 0 ? number_format($iterations) : '∞',
                    'keys' => number_format($keys),
                    'members' => number_format($members),
                    'cluster' => $clusterMode,
                    ...($commandFilters === [] ? [] : [
                        'command_filters' => $commandFilters,
                        'commands' => $resolvedCommandNames,
                    ]),
                ]
            );
        } catch (\Throwable) {
            // Ignore — logging only.
        }

        $iteration = 0;
        $totalRuns = 0;
        $totalCommands = 0;
        $startTime = \microtime(true);
        $commandStats = [];
        $lastAlwaysFalseWarningCommands = [];
        $lastAlwaysFalseWarningIteration = -1;

        try {
            while (true) {
                if ($iterations > 0 && $iteration >= $iterations) {
                    $this->logSummary('completed', $totalRuns, $totalCommands, $startTime);
                    return SymfonyCommand::SUCCESS;
                }

                $commandsFile = sprintf('%s/cmds.%d.jsonl', $workDir, $iteration);
                $outputOld = sprintf('%s/out_old.%d.jsonl', $workDir, $iteration);
                $outputNew = sprintf('%s/out_new.%d.jsonl', $workDir, $iteration);
                $iterationStart = \microtime(true);

                $this->logInfo('Iter start', [
                    'iter' => number_format($iteration),
                    'batch' => number_format($commandCount),
                ]);

                $generatorClusterOption = $clusterMode ? ' --cluster' : '';
                $generatorCommandFilters = $this->buildGeneratorCommandFilters($commandFilters);
                $cmd = sprintf(
                    '%s %s --keys=%d --members=%d%s%s %d %s',
                    escapeshellarg(PHP_BINARY),
                    escapeshellarg($generator),
                    $keys,
                    $members,
                    $generatorClusterOption,
                    $generatorCommandFilters,
                    $commandCount,
                    escapeshellarg($commandsFile)
                );
                $generatorResult = $this->runCommand($cmd);
                if ($generatorResult['code'] !== 0) {
                    $this->logError(
                        'gen-commands failed',
                        array_merge(
                            ['iter' => number_format($iteration)],
                            $this->commandFailureContext($cmd, $generatorResult)
                        )
                    );
                    $this->logSummary('gen-commands-failed', $totalRuns, $totalCommands, $startTime);
                    return self::EXIT_GEN_COMMANDS_FAILED;
                }

                $runnerClusterOption = $clusterMode ? ' --cluster' : '';
                $cmd = sprintf(
                    '%s %s%s %s %s %s %d',
                    escapeshellarg($phpOld),
                    escapeshellarg($runner),
                    $runnerClusterOption,
                    escapeshellarg($commandsFile),
                    escapeshellarg($outputOld),
                    escapeshellarg($host),
                    $port
                );
                $oldRunnerResult = $this->runCommand($cmd);
                if ($oldRunnerResult['code'] !== 0) {
                    $this->logError(
                        'run-commands failed',
                        array_merge(
                            [
                                'bin' => 'php-old',
                                'iter' => number_format($iteration),
                            ],
                            $this->commandFailureContext($cmd, $oldRunnerResult)
                        )
                    );
                    $this->logSummary('run-commands-failed', $totalRuns, $totalCommands, $startTime);
                    return self::EXIT_RUN_COMMANDS_OLD_FAILED;
                }

                $cmd = sprintf(
                    '%s %s%s %s %s %s %d',
                    escapeshellarg($phpNew),
                    escapeshellarg($runner),
                    $runnerClusterOption,
                    escapeshellarg($commandsFile),
                    escapeshellarg($outputNew),
                    escapeshellarg($host),
                    $port
                );
                $newRunnerResult = $this->runCommand($cmd);
                if ($newRunnerResult['code'] !== 0) {
                    $this->logError(
                        'run-commands failed',
                        array_merge(
                            [
                                'bin' => 'php-new',
                                'iter' => number_format($iteration),
                            ],
                            $this->commandFailureContext($cmd, $newRunnerResult)
                        )
                    );
                    $this->logSummary('run-commands-failed', $totalRuns, $totalCommands, $startTime);
                    return self::EXIT_RUN_COMMANDS_NEW_FAILED;
                }

                $this->updateAlwaysFalseStats($outputOld, $commandStats, $iteration);
                $this->updateAlwaysFalseStats($outputNew, $commandStats, $iteration);
                $this->maybeWarnAlwaysFalseCommands(
                    $commandStats,
                    $lastAlwaysFalseWarningCommands,
                    $lastAlwaysFalseWarningIteration,
                    $iteration,
                    $warningInterval
                );

                $cmd = sprintf(
                    '%s %s %s %s',
                    escapeshellarg(PHP_BINARY),
                    escapeshellarg($comparator),
                    escapeshellarg($outputOld),
                    escapeshellarg($outputNew)
                );
                $compareResult = $this->runCommand($cmd);
                $compareExitCode = $compareResult['code'];

                $totalRuns++;
                $totalCommands += $commandCount;

                if ($compareExitCode !== 0) {
                    $cleanupWorkDir = false;
                    $this->logError('Diff found', [
                        'iter' => number_format($iteration),
                        'cmdfile' => $commandsFile,
                        'old' => $outputOld,
                        'new' => $outputNew,
                        'dur' => $this->formatDuration(\microtime(true) - $iterationStart),
                        'command' => $cmd,
                    ]);
                    $this->outputDiffReplicationInstructions($comparator, $outputOld, $outputNew);
                    $this->logSummary('difference-detected', $totalRuns, $totalCommands, $startTime);
                    return SymfonyCommand::FAILURE;
                }

                $this->logInfo('Iter ok', [
                    'iter' => number_format($iteration),
                    'dur' => $this->formatDuration(\microtime(true) - $iterationStart),
                    'runs' => number_format($totalRuns),
                    'cmds' => number_format($totalCommands),
                ]);

                $this->cleanupIterationFiles($commandsFile, $outputOld, $outputNew);

                $iteration++;

                if ($sleepSeconds > 0) {
                    \sleep($sleepSeconds);
                }
            }
        } finally {
            $cleanup();
        }
    }

    private function getRedisVersion(string $phpBinary): string
    {
        return \trim((string) \shell_exec(
            sprintf(
                '%s -r %s',
                escapeshellarg($phpBinary),
                escapeshellarg("echo phpversion('redis');")
            )
        ));
    }

    private function buildWorkDir(): string
    {
        $work = \rtrim(\sys_get_temp_dir(), \DIRECTORY_SEPARATOR) .
            \DIRECTORY_SEPARATOR .
            'phpredis_diff_' .
            \getmypid();

        if (!@\mkdir($work, 0700) && !\is_dir($work)) {
            throw new \RuntimeException(sprintf('Failed to create work directory: %s', $work));
        }

        return $work;
    }

    private function cleanupIterationFiles(string ...$files): void
    {
        foreach ($files as $file) {
            if ($file === '' || !\is_file($file)) {
                continue;
            }

            @\unlink($file);
        }
    }

    /**
     * @return array{code:int, output:list<string>, duration:float}
     */
    private function runCommand(string $command): array
    {
        $this->logDebug('Executing command', ['command' => $command]);

        $start = \microtime(true);
        $output = [];
        $code = 0;
        exec($command . ' 2>&1', $output, $code);
        $duration = \microtime(true) - $start;

        $this->logDebug('Command completed', [
            'command' => $command,
            'exit_code' => (int) $code,
            'duration' => $this->formatDuration($duration),
        ]);

        return [
            'code' => (int) $code,
            'output' => $output,
            'duration' => $duration,
        ];
    }

    /**
     * @param list<string> $commandFilters
     */
    private function buildGeneratorCommandFilters(array $commandFilters): string
    {
        if ($commandFilters === []) {
            return '';
        }

        $fragments = [];
        foreach ($commandFilters as $filter) {
            $fragments[] = ' --commands=' . escapeshellarg($filter);
        }

        return implode('', $fragments);
    }

    private function logInfo(string $message, array $context = []): void
    {
        $this->logger?->info($message, $context);
    }

    private function logDebug(string $message, array $context = []): void
    {
        $this->logger?->debug($message, $context);
    }

    private function logWarning(string $message, array $context = []): void
    {
        $this->logger?->warning($message, $context);
    }

    private function logError(string $message, array $context = []): void
    {
        $this->logger?->error($message, $context);
    }

    private function logSummary(string $status, int $totalRuns, int $totalCommands, float $startTime): void
    {
        $this->logInfo('Summary', [
            'status' => $status,
            'runs' => number_format($totalRuns),
            'cmds' => number_format($totalCommands),
            'time' => $this->formatDuration(\microtime(true) - $startTime),
        ]);
    }

    /**
     * @param array{code:int, output:list<string>, duration:float} $result
     * @return array<string,mixed>
     */
    private function commandFailureContext(string $command, array $result): array
    {
        $context = [
            'command' => $command,
            'exit_code' => $result['code'],
            'duration' => $this->formatDuration($result['duration']),
        ];

        $output = $this->formatCommandOutput($result['output']);
        if ($output !== '') {
            $context['output'] = $output;
        }

        return $context;
    }

    /**
     * @param list<string> $lines
     */
    private function formatCommandOutput(array $lines): string
    {
        if ($lines === []) {
            return '';
        }

        $normalized = array_values(
            array_filter(
                array_map(static fn(string $line): string => \trim($line), $lines),
                static fn(string $line): bool => $line !== ''
            )
        );

        if ($normalized === []) {
            $normalized = $lines;
        }

        $maxLines = 5;
        $selected = array_slice($normalized, 0, $maxLines);
        $output = implode(' | ', $selected);

        $remaining = count($normalized) - count($selected);
        if ($remaining > 0) {
            $output .= sprintf(' (and %d more lines)', $remaining);
        }

        return $output;
    }

    private function normalizeLogLevel(string $level): string
    {
        $normalized = \strtolower($level);

        $aliases = [
            'warn' => 'warning',
            'err' => 'error',
            'crit' => 'critical',
            'fatal' => 'critical',
            'panic' => 'alert',
            'trace' => 'debug',
        ];

        return $aliases[$normalized] ?? $normalized;
    }

    /**
     * @param list<string> $filters
     * @return list<string>
     */
    private function resolveFilteredCommandNames(array $filters): array
    {
        $registry = new CommandRegistry();
        $commands = $registry->createInstances();

        $commands = array_values(
            array_filter(
                $commands,
                static fn (Command $command): bool => !$command->interactsWithExpiration()
            )
        );

        $filtered = CommandFilter::apply($commands, $filters);
        $names = array_map(
            static fn (Command $command): string => $command->getName(),
            $filtered
        );

        sort($names, \SORT_STRING);

        return $names;
    }

    /**
     * @param string|list<string>|null $value
     * @return list<string>
     */
    private function normalizeCommandFilters(mixed $value): array
    {
        if ($value === null || $value === []) {
            return [];
        }

        if (!\is_array($value)) {
            $value = [$value];
        }

        $filters = [];
        foreach ($value as $item) {
            if (!\is_string($item)) {
                continue;
            }

            foreach (\explode(',', $item) as $candidate) {
                $candidate = \trim($candidate);
                if ($candidate === '') {
                    continue;
                }

                $candidate = \trim($candidate, " \t\n\r\0\x0B'\"");
                if ($candidate === '') {
                    continue;
                }

                $filters[\strtoupper($candidate)] = $candidate;
            }
        }

        return array_values($filters);
    }

    private function formatDuration(float $seconds): string
    {
        if ($seconds < 0) {
            $seconds = 0.0;
        }

        if ($seconds < 1) {
            return sprintf('%dms', (int) round($seconds * 1000));
        }

        $parts = [];

        $hours = (int) floor($seconds / 3600);
        if ($hours > 0) {
            $parts[] = $hours . 'h';
            $seconds -= $hours * 3600;
        }

        $minutes = (int) floor($seconds / 60);
        if ($minutes > 0) {
            $parts[] = $minutes . 'm';
            $seconds -= $minutes * 60;
        }

        $seconds = round($seconds, 2);
        $parts[] = sprintf('%0.2fs', $seconds);

        return implode('', $parts);
    }

    /**
     * @param array<string, array{only_false:bool,count:int,false_count:int,first_iter:int,last_iter:int}> $stats
     */
    private function updateAlwaysFalseStats(string $path, array &$stats, int $iteration): void
    {
        if (!\is_readable($path)) {
            return;
        }

        $handle = @\fopen($path, 'rb');
        if ($handle === false) {
            $this->logDebug('Failed to open output file for tracking', ['path' => $path]);
            return;
        }

        while (($line = fgets($handle)) !== false) {
            $line = \trim($line);
            if ($line === '') {
                continue;
            }

            $record = \json_decode($line, true);
            if (!\is_array($record) || (($record['type'] ?? '') === 'meta')) {
                continue;
            }

            $command = $record['cmd'] ?? null;
            if (!\is_string($command) || $command === '') {
                continue;
            }

            if (!array_key_exists($command, $stats)) {
                $stats[$command] = [
                    'only_false' => true,
                    'count' => 0,
                    'false_count' => 0,
                    'first_iter' => $iteration,
                    'last_iter' => $iteration,
                ];
            } else {
                $stats[$command]['last_iter'] = $iteration;
            }

            $stats[$command]['count']++;

            if (($record['error'] ?? null) !== null) {
                $stats[$command]['only_false'] = false;
                continue;
            }

            $result = $record['result'] ?? null;
            if (
                \is_array($result) &&
                ($result['_type'] ?? null) === 'bool' &&
                array_key_exists('v', $result) &&
                $result['v'] === false
            ) {
                $stats[$command]['false_count']++;
                continue;
            }

            $stats[$command]['only_false'] = false;
        }

        \fclose($handle);
    }

    /**
     * @param array<string, array{only_false:bool,count:int,false_count:int,first_iter:int,last_iter:int}> $stats
     * @param list<string> $lastWarnedCommands
     */
    private function maybeWarnAlwaysFalseCommands(
        array $stats,
        array &$lastWarnedCommands,
        int &$lastWarningIteration,
        int $iteration,
        int $interval
    ): void {
        $candidates = [];
        foreach ($stats as $command => $info) {
            if ($info['only_false'] && $info['false_count'] > 0) {
                $candidates[$command] = $info;
            }
        }

        if ($candidates === []) {
            $lastWarnedCommands = [];
            $lastWarningIteration = -1;
            return;
        }

        $names = array_keys($candidates);
        sort($names);

        $shouldLog = $names !== $lastWarnedCommands;
        if (!$shouldLog && $interval > 0 && $lastWarningIteration >= 0) {
            $shouldLog = ($iteration - $lastWarningIteration) >= $interval;
        } elseif (!$shouldLog && $lastWarningIteration < 0) {
            $shouldLog = true;
        }

        if (!$shouldLog) {
            return;
        }

        $examples = [];
        foreach ($names as $name) {
            $examples[] = sprintf(
                '%s(count=%d,false=%d,first_iter=%d,last_iter=%d)',
                $name,
                $candidates[$name]['count'],
                $candidates[$name]['false_count'],
                $candidates[$name]['first_iter'],
                $candidates[$name]['last_iter']
            );

            if (count($examples) >= 5) {
                break;
            }
        }

        $context = [
            'total' => count($names),
            'examples' => $examples,
        ];

        if (count($names) > count($examples)) {
            $context['more'] = count($names) - count($examples);
        }

        $this->logWarning('Commands only returning false', $context);

        $lastWarnedCommands = $names;
        $lastWarningIteration = $iteration;
    }

    private function outputDiffReplicationInstructions(string $comparatorPath, string $oldPath, string $newPath): void
    {
        $command = $this->buildUserFacingDiffCommand($comparatorPath, $oldPath, $newPath);

        $this->io?->writeln('');
        $this->io?->error('Difference found. Run the following command to inspect the outputs:');
        $this->io?->writeln('  ' . $command);
        $this->io?->writeln('');
    }

    private function buildUserFacingDiffCommand(string $comparatorPath, string $oldPath, string $newPath): string
    {
        $comparator = $this->formatUserFacingCommandPath($comparatorPath, true);
        $old = $this->formatUserFacingCommandPath($oldPath, false);
        $new = $this->formatUserFacingCommandPath($newPath, false);

        return sprintf('%s %s %s', $comparator, $old, $new);
    }

    private function formatUserFacingCommandPath(string $path, bool $preferRelative): string
    {
        $display = $path;
        $resolved = \realpath($path);
        if ($resolved !== false) {
            $display = $resolved;
        }

        if ($preferRelative) {
            $cwd = \getcwd();
            if ($cwd !== false) {
                $cwd = \rtrim($cwd, \DIRECTORY_SEPARATOR);
                $prefix = $cwd . \DIRECTORY_SEPARATOR;
                if (\str_starts_with($display, $prefix)) {
                    $display = \substr($display, \strlen($prefix));
                }
            }
        }

        return $this->formatCommandToken($display);
    }

    private function formatCommandToken(string $token): string
    {
        if ($token === '') {
            return "''";
        }

        if (\preg_match('/^[A-Za-z0-9._\\/-:]+$/', $token) === 1) {
            return $token;
        }

        return escapeshellarg($token);
    }
}
