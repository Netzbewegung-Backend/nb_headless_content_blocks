<?php
declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray;

use TYPO3\CMS\Core\Resource\Collection\LazyFileReferenceCollection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LazyFileReferenceCollectionToArray
{

    public function __construct(protected LazyFileReferenceCollection $lazyFileReferenceCollection)
    {
        
    }

    public function toArray(): array
    {
        $data = [];
        foreach ($this->lazyFileReferenceCollection as $key => $value) {
            $data[$key] = GeneralUtility::makeInstance(FileReferenceToArray::class, $value)->toArray();
        }

        return $data;
    }
}
