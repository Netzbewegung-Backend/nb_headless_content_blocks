<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer;

use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\NormalizerInterface;
use TYPO3\CMS\Core\Domain\Record;

/**
 * Normalizes a resolved Record by delegating to the RecordArrayBuilder via
 * the Context, so nested records (relations, collection items) receive the
 * full conversion: identifier mapping, field transformers and the PSR-14
 * event.
 */
final class RecordNormalizer implements NormalizerInterface
{
    public function supports(mixed $value, Context $context): bool
    {
        return $value instanceof Record;
    }

    public function normalize(mixed $value, Context $context): mixed
    {
        return $context->buildRecord($value);
    }
}
