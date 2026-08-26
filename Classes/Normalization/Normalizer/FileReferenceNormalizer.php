<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer;

use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray\FileReferenceToArray;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\NormalizerInterface;
use TYPO3\CMS\Core\Resource\Collection\LazyFileReferenceCollection;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class FileReferenceNormalizer implements NormalizerInterface
{
    public function supports(mixed $value, Context $context): bool
    {
        return $value instanceof FileReference || $value instanceof LazyFileReferenceCollection;
    }

    public function normalize(mixed $value, Context $context): mixed
    {
        if ($value instanceof LazyFileReferenceCollection) {
            $normalized = [];
            foreach ($value as $key => $fileReference) {
                $normalized[$key] = $this->normalize($fileReference, $context);
            }

            return $normalized;
        }

        return GeneralUtility::makeInstance(FileReferenceToArray::class, $value)->toArray();
    }
}
