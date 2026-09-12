<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Schema;

/**
 * Lists the tt_content type names registered in TCA, so the combined
 * schema can add fallback branches for elements that are not Content
 * Blocks (core types like "html" or "shortcut", classic plugins).
 */
final class TcaContentTypesProvider
{
    /**
     * @return list<string> sorted type names
     */
    public function getTypeNames(): array
    {
        $types = $GLOBALS['TCA']['tt_content']['types'] ?? [];
        if (!is_array($types)) {
            return [];
        }

        $typeNames = array_filter(array_keys($types), 'is_string');
        sort($typeNames);

        return $typeNames;
    }
}
