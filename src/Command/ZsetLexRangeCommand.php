<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

abstract class ZsetLexRangeCommand extends KeyCommand
{
    /**
     * @return string[]
     */
    protected function randomLexRange(): array
    {
        $case = random_int(0, 3);

        if ($case === 0) {
            return ['-', '+'];
        }

        if ($case === 1) {
            return ['-', $this->randomUpperLexBound($this->randomLexValue())];
        }

        if ($case === 2) {
            return [$this->randomLowerLexBound($this->randomLexValue()), '+'];
        }

        $values = [$this->randomLexValue(), $this->randomLexValue()];
        sort($values, SORT_STRING);

        return [
            $this->randomLowerLexBound($values[0]),
            $this->randomUpperLexBound($values[1]),
        ];
    }

    private function randomLexValue(): string
    {
        return $this->randomAscii(1, 10);
    }

    private function randomLowerLexBound(string $value): string
    {
        $prefix = random_int(0, 1) === 0 ? '[' : '(';

        return $prefix . $value;
    }

    private function randomUpperLexBound(string $value): string
    {
        $prefix = random_int(0, 1) === 0 ? '[' : '(';

        return $prefix . $value;
    }
}

