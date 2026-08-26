<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer;

use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\NormalizerInterface;

final class DateTimeNormalizer implements NormalizerInterface
{
    public const DEFAULT_FORMAT = \DateTimeInterface::W3C;

    public function supports(mixed $value, Context $context): bool
    {
        return $value instanceof \DateTimeInterface;
    }

    public function normalize(mixed $value, Context $context): mixed
    {
        $format = $context->getOption('dateTimeFormat', self::DEFAULT_FORMAT);

        return $value->format(is_string($format) ? $format : self::DEFAULT_FORMAT);
    }
}
