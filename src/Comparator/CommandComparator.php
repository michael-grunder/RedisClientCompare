<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Comparator;

use RuntimeException;
use SplFileObject;

final class CommandComparator
{
    public function compare(string $fileA, string $fileB): ComparisonResult
    {
        if (!is_readable($fileA) || !is_readable($fileB)) {
            throw new RuntimeException('Both input files must be readable.');
        }

        $readerA = new SplFileObject($fileA, 'r');
        $readerB = new SplFileObject($fileB, 'r');

        $index = 0;
        while (true) {
            $recordA = $this->nextCommandRecord($readerA);
            $recordB = $this->nextCommandRecord($readerB);

            if ($recordA === null && $recordB === null) {
                return new ComparisonResult(true);
            }

            if ($recordA === null || $recordB === null) {
                return new ComparisonResult(
                    false,
                    $index,
                    $recordA,
                    $recordB,
                    'DIFFERENT LENGTH'
                );
            }

            $normalizedA = $this->normalizeRecord($recordA);
            $normalizedB = $this->normalizeRecord($recordB);

            if ($normalizedA !== $normalizedB) {
                return new ComparisonResult(
                    false,
                    $index,
                    $recordA,
                    $recordB,
                    $recordA['cmd'] ?? 'unknown'
                );
            }

            $index++;
        }
    }

    private function nextCommandRecord(SplFileObject $reader): ?array
    {
        while (!$reader->eof()) {
            $line = trim($reader->fgets());
            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true);
            if (!is_array($decoded)) {
                continue;
            }

            if (($decoded['type'] ?? null) === 'meta') {
                continue;
            }

            return $decoded;
        }

        return null;
    }

    private function normalizeRecord(array $record): array
    {
        return [
            'cmd' => $record['cmd'] ?? null,
            'args' => $record['args'] ?? null,
            'result' => $record['result'] ?? null,
            'error' => $record['error'] ?? null,
        ];
    }
}
