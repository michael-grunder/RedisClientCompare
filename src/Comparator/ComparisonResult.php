<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Comparator;

final class ComparisonResult
{
    public function __construct(
        private readonly bool $identical,
        private readonly ?int $index = null,
        private readonly ?array $recordA = null,
        private readonly ?array $recordB = null,
        private readonly ?string $message = null,
    ) {
    }

    public function isIdentical(): bool
    {
        return $this->identical;
    }

    public function getIndex(): ?int
    {
        return $this->index;
    }

    public function getRecordA(): ?array
    {
        return $this->recordA;
    }

    public function getRecordB(): ?array
    {
        return $this->recordB;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }
}
