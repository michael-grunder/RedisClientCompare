<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Console;

use Michaelgrunder\RedisClientCompare\Console\Command\CompareLoopCommand;
use Michaelgrunder\RedisClientCompare\Console\Command\CompareOutputsCommand;
use Michaelgrunder\RedisClientCompare\Console\Command\GenerateCommandsCommand;
use Michaelgrunder\RedisClientCompare\Console\Command\ListMissingCommandsCommand;
use Michaelgrunder\RedisClientCompare\Console\Command\RunCommandsCommand;
use Symfony\Component\Console\Application;

final class ApplicationFactory
{
    private function __construct()
    {
    }

    public static function create(): Application
    {
        $application = new Application('Redis Client Compare', 'dev');

        $application->add(new GenerateCommandsCommand());
        $application->add(new RunCommandsCommand());
        $application->add(new CompareOutputsCommand());
        $application->add(new CompareLoopCommand());
        $application->add(new ListMissingCommandsCommand());

        return $application;
    }
}
