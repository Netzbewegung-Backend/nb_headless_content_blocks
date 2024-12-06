<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\JsonSerializable;

use JsonSerializable;
use TYPO3\CMS\Core\Resource\Collection\LazyFileReferenceCollection;

class LazyFileReferenceCollectionJsonSerializable implements JsonSerializable
{
    public function __construct(protected LazyFileReferenceCollection $lazyFileReferenceCollection)
    {

    }

    public function jsonSerialize(): mixed
    {
        $data = [];
        foreach ($this->lazyFileReferenceCollection as $key => $value) {
            $data[$key] = new FileReferenceJsonSerializable($value);
        }
        return $data;
    }
}
