<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\JsonSerializable;

use JsonSerializable;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;
use TYPO3\CMS\Core\Domain\FlexFormFieldValues;
use TYPO3\CMS\Core\LinkHandling\TypolinkParameter;
use TYPO3\CMS\Core\Resource\Collection\LazyFileReferenceCollection;
use TYPO3\CMS\Core\Resource\FileReference;

class ArrayRecursiveJsonSerializable implements JsonSerializable
{
    public function __construct(protected array $array)
    {

    }

    public function jsonSerialize(): mixed
    {
        $data = [];

        foreach ($this->array as $key => $value) {
            switch (true) {
                case is_array($value):
                    $data[$key] = new ArrayRecursiveJsonSerializable($value);
                    break;
                case $value instanceof FlexFormFieldValues:
                    $data[$key] = $value->toArray();
                    break;
                case $value instanceof TypolinkParameter:
                    $data[$key] = new TypolinkParameterJsonSerializable($value);
                    break;
                case $value instanceof LazyRecordCollection:
                    $data[$key] = new LazyRecordCollectionJsonSerializable($value);
                    break;
                case $value instanceof LazyFileReferenceCollection:
                    $data[$key] = new LazyFileReferenceCollectionJsonSerializable($value);
                    break;
                case $value instanceof FileReference:
                    $data[$key] = new FileReferenceJsonSerializable($value);
                    break;
                default:
                    $data[$key] = $value;
            }
        }

        return $data;
    }
}
