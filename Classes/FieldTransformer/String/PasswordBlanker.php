<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\FieldTransformer\String;

use Netzbewegung\NbHeadlessContentBlocks\FieldTransformer\FieldValueTransformerInterface;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\PasswordFieldType;

/**
 * Password values are never sent to headless clients.
 */
final class PasswordBlanker implements FieldValueTransformerInterface
{
    public function supports(FieldTypeInterface $field): bool
    {
        return $field instanceof PasswordFieldType
            || $field->getType() === TableColumnType::PASSWORD->value;
    }

    public function transform(string $value, FieldTypeInterface $field): string
    {
        return '';
    }
}
