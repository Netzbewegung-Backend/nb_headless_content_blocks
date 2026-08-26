<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer;

use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\NormalizerInterface;

/**
 * Passes through null, integers, strings and plain arrays (recursing into
 * array values via the chain).
 */
final class ScalarNormalizer implements NormalizerInterface
{
    public function supports(mixed $value, Context $context): bool
    {
        return $value === null
            || is_int($value)
            || is_string($value)
            || is_array($value);
    }

    public function normalize(mixed $value, Context $context): mixed
    {
        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $context->getChain()->normalize($item, $context);
            }

            return $normalized;
        }

        return $value;
    }
}
