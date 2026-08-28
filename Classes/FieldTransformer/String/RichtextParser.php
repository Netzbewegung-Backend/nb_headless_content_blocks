<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\FieldTransformer\String;

use Netzbewegung\NbHeadlessContentBlocks\FieldTransformer\FieldValueTransformerInterface;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\TextFieldType;

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

    public function transform(string $value, FieldTypeInterface $field, Context $context): string
    {
        $contentObject = $context->getContentObjectRenderer();

        if ($contentObject === null) {
            // Without a ContentObjectRenderer (container-less usage) there is
            // no frontend TypoScript to parse the value with.
            return $value;
        }

        return $contentObject->parseFunc($value, null, '< lib.parseFunc_RTE');
    }
}
