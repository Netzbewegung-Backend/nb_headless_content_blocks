<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\ContentBlocks;

use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;

/**
 * Maps Content Block columns to field identifiers via the ContentBlocks
 * TableDefinitionCollection (the only remaining ContentBlocks dependency).
 */
final class ContentBlocksIdentifierMapper implements IdentifierMapperInterface
{
    public function __construct(
        private readonly TableDefinitionCollection $tableDefinitionCollection,
    ) {}

    public function mapColumnToIdentifier(string $table, ?string $recordType, string $column): ?string
    {
        if (!$this->tableDefinitionCollection->hasTable($table)) {
            return null;
        }

        $tableDefinition = $this->tableDefinitionCollection->getTable($table);

        if (!$tableDefinition->tcaFieldDefinitionCollection->hasField($column)) {
            return null;
        }

        return $tableDefinition->tcaFieldDefinitionCollection->getField($column)->identifier;
    }
}
