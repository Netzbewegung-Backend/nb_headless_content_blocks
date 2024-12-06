<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\JsonSerializable;

use JsonSerializable;
use TYPO3\CMS\Core\Resource\FileReference;

class FileReferenceJsonSerializable implements JsonSerializable
{
    public function __construct(protected FileReference $fileReference)
    {

    }

    public function jsonSerialize(): mixed
    {
        return [
            'alt' => $this->fileReference->getAlternative(),
            'title' => $this->fileReference->getTitle(),
            'publicUrl' => $this->fileReference->getPublicUrl()
        ];
    }
}
