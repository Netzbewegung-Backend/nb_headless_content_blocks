<?php

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\JsonSerializable;

use JsonSerializable;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;

class LazyRecordCollectionJsonSerializable implements JsonSerializable
{
    public function __construct(protected LazyRecordCollection $lazyRecordCollection)
    {

    }

    public function jsonSerialize(): mixed
    {
        $data = [];
        foreach ($this->lazyRecordCollection as $key => $value) {
            $data[$key] = new ContentBlockDataJsonSerializable($value);
        }
        return $data;
    }
}
