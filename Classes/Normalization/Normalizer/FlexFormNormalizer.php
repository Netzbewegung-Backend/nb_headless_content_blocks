<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer;

use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\NormalizerInterface;
use TYPO3\CMS\Core\Domain\FlexFormFieldValues;

final class FlexFormNormalizer implements NormalizerInterface
{
    public function supports(mixed $value, Context $context): bool
    {
        return $value instanceof FlexFormFieldValues;
    }

    public function normalize(mixed $value, Context $context): mixed
    {
        return $value->toArray();
    }
}
