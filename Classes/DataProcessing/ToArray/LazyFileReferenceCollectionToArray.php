<?php
declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray;

use TYPO3\CMS\Core\Resource\Collection\LazyFileReferenceCollection;

class LazyFileReferenceCollectionToArray
{

    public function __construct(protected LazyFileReferenceCollection $lazyFileReferenceCollection)
    {
        
    }

    public function toArray(): array
    {
        $data = [];
        foreach ($this->lazyFileReferenceCollection as $key => $value) {
            $data[$key] = (new FileReferenceToArray($value))->toArray();
        }

        return $data;
    }
}
