<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Normalization;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Schema\TcaSchema;

/**
 * Carries the state of a single normalization run: the current table schema,
 * the PSR-7 request and options forwarded from the DataProcessor
 * (TypoScript "options.").
 */
final class Context
{
    private ?NormalizerChain $chain = null;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private readonly ?TcaSchema $tcaSchema,
        private readonly ?ServerRequestInterface $request,
        private readonly array $options,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * Called by the RecordNormalizer when creating sub-contexts, so normalizers
     * can recurse (e.g. array items, related records) without a circular
     * constructor dependency.
     */
    public function setChain(NormalizerChain $chain): void
    {
        $this->chain = $chain;
    }

    public function getChain(): NormalizerChain
    {
        if ($this->chain === null) {
            throw new \LogicException('NormalizerChain is not set on this Context. It is set automatically by the RecordNormalizer.', 1782745501);
        }

        return $this->chain;
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
     * relation), keeping request, options and event dispatcher.
     */
    public function withTcaSchema(?TcaSchema $tcaSchema): self
    {
        $context = new self($tcaSchema, $this->request, $this->options, $this->eventDispatcher);
        $context->setChain($this->chain);

        return $context;
    }
}
