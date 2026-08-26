<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer;

use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray\TypolinkParameterToArray;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\NormalizerInterface;
use TYPO3\CMS\Core\LinkHandling\TypolinkParameter;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class TypolinkNormalizer implements NormalizerInterface
{
    public function supports(mixed $value, Context $context): bool
    {
        return $value instanceof TypolinkParameter;
    }

    public function normalize(mixed $value, Context $context): mixed
    {
        return GeneralUtility::makeInstance(TypolinkParameterToArray::class, $value)->toArray();
    }
}
