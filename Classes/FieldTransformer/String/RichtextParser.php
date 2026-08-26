<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\FieldTransformer\String;

use Netzbewegung\NbHeadlessContentBlocks\FieldTransformer\FieldValueTransformerInterface;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\TextFieldType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Rich text areas are rendered through lib.parseFunc_RTE, plain text areas
 * are passed through unchanged.
 */
final class RichtextParser implements FieldValueTransformerInterface
{
    public function supports(FieldTypeInterface $field): bool
    {
        return $field instanceof TextFieldType && $field->isRichText();
    }

    public function transform(string $value, FieldTypeInterface $field): string
    {
        $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        return $contentObject->parseFunc($value, null, '< lib.parseFunc_RTE');
    }
}
