<?php

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\JsonSerializable;

use JsonSerializable;
use ReflectionClass;
use TYPO3\CMS\ContentBlocks\DataProcessing\ContentBlockData;

class ContentBlockDataJsonSerializable implements JsonSerializable
{
    public function __construct(protected ContentBlockData $contentBlockData)
    {

    }

    public function jsonSerialize(): mixed
    {
        // Evil code never dies
        $reflection = new ReflectionClass($this->contentBlockData);
        $property = $reflection->getProperty('_processed');
        $property->setAccessible(true);
        $data = $property->getValue($this->contentBlockData);

        return new ArrayRecursiveJsonSerializable($data);
    }
}
