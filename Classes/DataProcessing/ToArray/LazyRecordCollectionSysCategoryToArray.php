<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray;

use TYPO3\CMS\Core\Collection\LazyRecordCollection;

class LazyRecordCollectionSysCategoryToArray
{
    public function __construct(
        protected LazyRecordCollection $lazyRecordCollection,
    ) {

    }

    public function toArray(): array
    {
        $data = [];

        foreach ($this->lazyRecordCollection as $key => $value) {
            $array = $value->toArray();
            $data[$key] = [
                'uid' => $array['uid'],
                'pid' => $array['pid'],
                'title' => $array['title'],
            ];
        }

        return $data;
    }
}
