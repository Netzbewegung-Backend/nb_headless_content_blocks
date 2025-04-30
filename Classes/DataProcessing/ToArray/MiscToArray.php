<?php
declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray;

use Exception;
use TYPO3\CMS\ContentBlocks\FieldType\FieldTypeInterface;
use TYPO3\CMS\ContentBlocks\FieldType\FileFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\SelectFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\TextareaFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\TextFieldType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class MiscToArray
{

    public function __construct(
        protected mixed $value,
        protected FieldTypeInterface $fieldType
    )
    {
        
    }

    public function toArray(): mixed
    {
        switch (true) {
            case $this->fieldType instanceof FileFieldType;
            case $this->fieldType instanceof SelectFieldType:
            case $this->fieldType instanceof TextFieldType:
                break;
            case $this->fieldType instanceof TextareaFieldType:
                $enableRichtext = $this->fieldType->getTca()['config']['enableRichtext'] ?? false;
                if ($enableRichtext === true) {
                    $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
                    return $contentObject->parseFunc($this->value, null, '< lib.parseFunc_RTE');
                }

                break;
            default:
                throw new Exception('Unknown default case in ArrayRecursiveToArray default case for key "' . $key . '"', 6848262796);
        }

        return $this->value;
    }
}
