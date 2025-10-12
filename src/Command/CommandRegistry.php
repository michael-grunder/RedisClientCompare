<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

use ReflectionClass;
use RuntimeException;

/**
 * Discovers available command classes at runtime.
 */
final class CommandRegistry
{
    /**
     * @return class-string<Command>[]
     */
    public function findCommandClasses(): array
    {
        $directory = __DIR__;
        $namespace = __NAMESPACE__;

        $files = glob($directory . '/*.php');
        if ($files === false) {
            throw new RuntimeException(sprintf('Failed to glob command classes in %s', $directory));
        }

        sort($files);

        $classes = [];
        foreach ($files as $file) {
            $className = $namespace . '\\' . basename($file, '.php');

            if ($className === __CLASS__ || $className === Command::class) {
                continue;
            }

            if (!is_subclass_of($className, Command::class)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            if ($reflection->isAbstract()) {
                continue;
            }

            $classes[] = $className;
        }

        return $classes;
    }

    /**
     * @return Command[]
     */
    public function createInstances(): array
    {
        return array_map(static fn (string $class): Command => new $class(), $this->findCommandClasses());
    }
}
