<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Normalization;

/**
 * A normalizer converts a single rich value into a JSON-compatible
 * plain PHP value (array, string, int, float, bool or null).
 *
 * Normalizers are registered as tagged services ("nb_headless.normalizer")
 * and are consulted by the NormalizerChain in registration order. The first
 * normalizer whose supports() returns true wins.
 */
interface NormalizerInterface
{
    /**
     * @param mixed $value value to normalize (may be any rich domain object or scalar)
     */
    public function supports(mixed $value, Context $context): bool;

    /**
     * @return mixed JSON-compatible representation of $value
     */
    public function normalize(mixed $value, Context $context): mixed;
}
