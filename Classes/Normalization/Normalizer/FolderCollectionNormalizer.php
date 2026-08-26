<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer;

use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray\LazyFolderCollectionToArray;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\NormalizerInterface;
use TYPO3\CMS\Core\Resource\Collection\LazyFolderCollection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class FolderCollectionNormalizer implements NormalizerInterface
{
    public function supports(mixed $value, Context $context): bool
    {
        return $value instanceof LazyFolderCollection;
    }

    public function normalize(mixed $value, Context $context): mixed
    {
        return GeneralUtility::makeInstance(LazyFolderCollectionToArray::class, $value)->toArray();
    }
}
