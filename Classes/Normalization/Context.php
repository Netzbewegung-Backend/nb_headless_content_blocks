<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Normalization;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Schema\TcaSchema;

/**
 * Carries the state of a single normalization run: the current table schema,
 * the PSR-7 request, options forwarded from the DataProcessor (TypoScript
 * "options.") and per-field image processing definitions.
 */
final class Context
{
    private ?NormalizerChain $chain = null;
    private ?\Closure $recordBuilder = null;
    private string $currentFieldIdentifier = '';

    /**
     * @param array<string, mixed> $options
     * @param array<string, array<string, string>> $fileProcessing field identifier => variant name => options string
     */
    public function __construct(
        private readonly ?TcaSchema $tcaSchema,
        private readonly ?ServerRequestInterface $request,
        private readonly array $options,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly array $fileProcessing = [],
    ) {}

    /**
     * Called by the RecordArrayBuilder when creating (sub-)contexts, so
     * normalizers can recurse without a circular constructor dependency.
     */
    public function setChain(NormalizerChain $chain): void
    {
        $this->chain = $chain;
    }

    public function getChain(): NormalizerChain
    {
        if ($this->chain === null) {
            throw new \LogicException('NormalizerChain is not set on this Context. It is set automatically by the RecordArrayBuilder.', 1782745501);
        }

        return $this->chain;
    }

    /**
     * The record builder closure allows the RecordNormalizer to delegate
     * nested records back to the full conversion (identifier mapping,
     * transformers, event), avoiding a circular service dependency.
     */
    public function setRecordBuilder(\Closure $recordBuilder): void
    {
        $this->recordBuilder = $recordBuilder;
    }

    /**
     * @param array<string, mixed> $typoScriptOptions
     * @return array<string, mixed>
     */
    public function buildRecord(RecordInterface $record, array $typoScriptOptions = []): array
    {
        if ($this->recordBuilder === null) {
            throw new \LogicException('No record builder is set on this Context. It is set automatically by the RecordArrayBuilder.', 1782745502);
        }

        return ($this->recordBuilder)($record, $typoScriptOptions);
    }

    public function getTcaSchema(): ?TcaSchema
    {
        return $this->tcaSchema;
    }

    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    /**
     * Creates a context for a related table (e.g. for records of a resolved
     * relation), keeping request, options, event dispatcher and the record
     * builder.
     */
    public function withTcaSchema(?TcaSchema $tcaSchema): self
    {
        $context = new self($tcaSchema, $this->request, $this->options, $this->eventDispatcher, $this->fileProcessing);
        $context->setChain($this->chain);
        $context->setRecordBuilder($this->recordBuilder);

        return $context;
    }

    /**
     * Image processing definitions for the field currently being normalized
     * (from Content Block headless.yaml, overridable via TypoScript options).
     *
     * @return array<string, string> variant name => options string
     */
    public function getFileProcessingForCurrentField(): array
    {
        return $this->fileProcessing[$this->currentFieldIdentifier] ?? [];
    }

    public function withCurrentFieldIdentifier(string $fieldIdentifier): self
    {
        $context = new self($this->tcaSchema, $this->request, $this->options, $this->eventDispatcher, $this->fileProcessing);
        $context->setChain($this->chain);
        $context->setRecordBuilder($this->recordBuilder);
        $context->currentFieldIdentifier = $fieldIdentifier;

        return $context;
    }
}
