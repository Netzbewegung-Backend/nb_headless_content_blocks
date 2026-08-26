<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\FieldTransformer;

use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;

/**
 * Transforms a raw string field value into its JSON representation, based on
 * the field's schema information (e.g. blank passwords, parse richtext).
 */
interface FieldValueTransformerInterface
{
    public function supports(FieldTypeInterface $field): bool;

    public function transform(string $value, FieldTypeInterface $field): string;
}
