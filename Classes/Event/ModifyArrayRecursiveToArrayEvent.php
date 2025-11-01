<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Event;

use TYPO3\CMS\ContentBlocks\Definition\TcaFieldDefinition;

final class ModifyArrayRecursiveToArrayEvent
{
    private bool $handled = false;
    private mixed $processedValue = null;

    public function __construct(
        private readonly string|int $key,
        private readonly mixed $value,
        private readonly ?TcaFieldDefinition $tcaFieldDefinition
    ) {
    }

    public function getKey(): string|int
    {
        return $this->key;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getTcaFieldDefinition(): ?TcaFieldDefinition
    {
        return $this->tcaFieldDefinition;
    }

    public function setProcessedValue(mixed $value): void
    {
        $this->processedValue = $value;
        $this->handled = true;
    }

    public function getProcessedValue(): mixed
    {
        return $this->processedValue;
    }

    public function isHandled(): bool
    {
        return $this->handled;
    }
}
