<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

final class CommandFilter
{
    /**
     * @param Command[] $commands
     * @param list<string> $filters
     * @return Command[]
     */
    public static function apply(array $commands, array $filters): array
    {
        [$includes, $excludes] = self::parseFilters($filters);

        $pool = $includes === []
            ? $commands
            : array_values(
                array_filter(
                    $commands,
                    static fn (Command $command): bool => self::matchesAny($command, $includes)
                )
            );

        if ($excludes === []) {
            return $pool;
        }

        return array_values(
            array_filter(
                $pool,
                static fn (Command $command): bool => !self::matchesAny($command, $excludes)
            )
        );
    }

    /**
     * @param list<string> $filters
     * @return array{0:list<array{type:'name'|'category',pattern:string}>,1:list<array{type:'name'|'category',pattern:string}>}
     */
    private static function parseFilters(array $filters): array
    {
        $includes = [];
        $excludes = [];

        foreach ($filters as $filter) {
            $directive = self::normalizeFilter($filter);
            if ($directive === null) {
                continue;
            }

            if ($directive['exclude']) {
                $excludes[] = [
                    'type' => $directive['type'],
                    'pattern' => $directive['pattern'],
                ];
                continue;
            }

            $includes[] = [
                'type' => $directive['type'],
                'pattern' => $directive['pattern'],
            ];
        }

        return [$includes, $excludes];
    }

    /**
     * @return array{exclude:bool,type:'name'|'category',pattern:string}|null
     */
    private static function normalizeFilter(string $filter): ?array
    {
        $candidate = trim($filter);
        if ($candidate === '') {
            return null;
        }

        $exclude = false;
        if ($candidate[0] === '!') {
            $exclude = true;
            $candidate = ltrim(substr($candidate, 1));
        }

        if ($candidate === '') {
            return null;
        }

        $type = 'name';
        if ($candidate[0] === '@') {
            $type = 'category';
            $candidate = ltrim(substr($candidate, 1));
        }

        $pattern = strtoupper($candidate);
        if ($pattern === '') {
            return null;
        }

        return [
            'exclude' => $exclude,
            'type' => $type === 'category' ? 'category' : 'name',
            'pattern' => $pattern,
        ];
    }

    /**
     * @param list<array{type:'name'|'category',pattern:string}> $directives
     */
    private static function matchesAny(Command $command, array $directives): bool
    {
        foreach ($directives as $directive) {
            if (self::matchesDirective($command, $directive)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array{type:'name'|'category',pattern:string} $directive
     */
    private static function matchesDirective(Command $command, array $directive): bool
    {
        $pattern = $directive['pattern'];

        if ($directive['type'] === 'category') {
            $dataType = $command->getDataType();
            if ($dataType === null) {
                return false;
            }

            return self::matchesPattern($pattern, strtoupper($dataType));
        }

        return self::matchesPattern($pattern, strtoupper($command->getName()));
    }

    private static function matchesPattern(string $pattern, string $value): bool
    {
        if ($pattern === '') {
            return false;
        }

        if (function_exists('fnmatch')) {
            return fnmatch($pattern, $value);
        }

        $escaped = preg_quote($pattern, '/');
        $escaped = str_replace(['\*', '\?'], ['.*', '.'], $escaped);

        return (bool) preg_match('/^' . $escaped . '$/', $value);
    }
}
