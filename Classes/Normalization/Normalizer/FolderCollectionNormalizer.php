<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer;

use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\NormalizerInterface;
use TYPO3\CMS\Core\Resource\Collection\LazyFolderCollection;

final class FolderCollectionNormalizer implements NormalizerInterface
{
    public function supports(mixed $value, Context $context): bool
    {
        return $value instanceof LazyFolderCollection;
    }

    public function normalize(mixed $value, Context $context): mixed
    {
        $data = [];
        foreach ($value as $key => $folder) {
            $path = '/' . $folder->getStorage()->getConfiguration()['basePath'] . ltrim((string)$folder->getIdentifier(), '/');
            $data[$key] = $path;
        }

        return $data;
    }
}
