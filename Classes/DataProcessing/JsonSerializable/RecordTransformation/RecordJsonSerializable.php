<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\JsonSerializable\RecordTransformation;

use JsonSerializable;
use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\JsonSerializable\RecordTransformation\ArrayRecursiveJsonSerializable;
use TYPO3\CMS\Core\Domain\Record;

class RecordJsonSerializable implements JsonSerializable
{
    public function __construct(protected Record $record)
    {

    }

    public function jsonSerialize(): mixed
    {
        return new ArrayRecursiveJsonSerializable($this->record->toArray());
    }
}
