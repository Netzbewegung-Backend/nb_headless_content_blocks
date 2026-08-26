<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\ContentBlocks;

/**
 * Maps database column names of Content Block fields to their Content Block
 * field identifiers (e.g. "tx_ext_my_field" -> "my_field") and vice versa.
 *
 * Default implementation uses the ContentBlocks TableDefinitionCollection.
 */
interface IdentifierMapperInterface
{
    /**
     * Returns the Content Block field identifier for a column of the given
     * table / record type, or null when the column is not a Content Block
     * field of that type.
     */
    public function mapColumnToIdentifier(string $table, ?string $recordType, string $column): ?string;
}
