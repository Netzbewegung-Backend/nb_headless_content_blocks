<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Normalization;

/**
 * Dispatches a value to the first registered normalizer that supports it.
 * Falls back to the UnknownTypeNormalizer (null + debug log) so no value
 * type is ever silently dropped.
 */
final class NormalizerChain
{
    /**
     * @param iterable<NormalizerInterface> $normalizers
     */
    public function __construct(
        private readonly iterable $normalizers,
        private readonly UnknownTypeNormalizer $unknownTypeNormalizer,
    ) {}

    public function normalize(mixed $value, Context $context): mixed
    {
        foreach ($this->normalizers as $normalizer) {
            if ($normalizer->supports($value, $context)) {
                return $normalizer->normalize($value, $context);
            }
        }

        return $this->unknownTypeNormalizer->normalize($value, $context);
    }
}
