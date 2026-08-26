<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Normalization;

use Psr\Log\LoggerAwareTrait;

/**
 * Last resort for value types no other normalizer supports: emits null
 * (never breaks the JSON response) and logs the dropped type for debugging.
 */
final class UnknownTypeNormalizer implements NormalizerInterface
{
    use LoggerAwareTrait;

    public function supports(mixed $value, Context $context): bool
    {
        return true;
    }

    public function normalize(mixed $value, Context $context): mixed
    {
        $this->logger?->debug(
            'Value of type {type} could not be normalized to a JSON representation and was replaced with null.',
            ['type' => get_debug_type($value)]
        );

        return null;
    }
}
