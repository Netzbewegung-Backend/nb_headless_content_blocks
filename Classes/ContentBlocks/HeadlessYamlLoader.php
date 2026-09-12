<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\ContentBlocks;

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\ContentBlocks\Registry\ContentBlockRegistry;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Loads per-Content-Block headless declarations from an optional
 * headless.yaml file next to the Content Block config.yaml:
 *
 * Image processing variants per field:
 *
 *     fields:
 *       image:
 *         processing:
 *           mobile: "width=883c,fileExtension=webp"
 *           desktop: "width=1564c,fileExtension=webp"
 *
 * Rendered child element lists of container blocks (the JSON keys the
 * site package renders children into, TypoScript "as"):
 *
 *     children:
 *       - main
 *       - left
 *       - right
 *
 * Declared JSON Schema fragments for additional rendered keys (sub data
 * processors, headless.php results — everything not derivable from the
 * Content Block definition):
 *
 *     properties:
 *       categories:
 *         type: array
 *         items:
 *           type: string
 *
 * Processing values follow the ext:headless ProcessingConfiguration option
 * syntax ("key=value" pairs, comma separated).
 */
final class HeadlessYamlLoader
{
    private const FILENAME = 'headless.yaml';

    public function __construct(
        private readonly ?PhpFrontend $cache = null,
        private readonly ?ContentBlockRegistry $contentBlockRegistry = null,
    ) {}

    /**
     * @return array<string, array<string, string>> field identifier => variant name => options string
     */
    public function getProcessingForContentBlock(string $contentBlockName): array
    {
        $config = $this->loadConfig($contentBlockName);

        $processing = [];
        foreach ($config['fields'] ?? [] as $fieldKey => $field) {
            if (!is_array($field) || !isset($field['processing']) || !is_array($field['processing'])) {
                continue;
            }

            // Field identifier either as array key or as "identifier" value
            $fieldIdentifier = is_string($fieldKey) ? $fieldKey : (string)($field['identifier'] ?? '');
            if ($fieldIdentifier === '') {
                continue;
            }

            $processing[$fieldIdentifier] = array_map(
                static fn(mixed $value): string => (string)$value,
                $field['processing']
            );
        }

        return $processing;
    }

    /**
     * JSON keys of rendered child element lists, as declared in the
     * optional "children" section.
     *
     * @return list<string>
     */
    public function getChildrenForContentBlock(string $contentBlockName): array
    {
        $config = $this->loadConfig($contentBlockName);

        $children = $config['children'] ?? [];
        if (!is_array($children)) {
            return [];
        }

        $childKeys = [];
        foreach ($children as $child) {
            if (is_string($child) && $child !== '' && !in_array($child, $childKeys, true)) {
                $childKeys[] = $child;
            }
        }

        return $childKeys;
    }

    /**
     * Declared JSON Schema fragments for additional rendered keys, as
     * declared in the optional "properties" section. They are merged
     * verbatim into the block's data properties.
     *
     * @return array<string, mixed> property key => JSON Schema fragment
     */
    public function getDeclaredPropertiesForContentBlock(string $contentBlockName): array
    {
        $config = $this->loadConfig($contentBlockName);

        $properties = $config['properties'] ?? [];
        if (!is_array($properties)) {
            return [];
        }

        $declaredProperties = [];
        foreach ($properties as $propertyKey => $schema) {
            if (is_string($propertyKey) && $propertyKey !== '' && is_array($schema)) {
                $declaredProperties[$propertyKey] = $schema;
            }
        }

        return $declaredProperties;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadConfig(string $contentBlockName): array
    {
        $filePath = $this->resolveFilePath($contentBlockName);
        if ($filePath === '') {
            return [];
        }

        // The file modification time is part of the cache identifier, so a
        // changed headless.yaml is picked up automatically (the entry itself
        // lives until the TYPO3 caches are flushed).
        $cacheIdentifier = 'headless_yaml_' . md5($contentBlockName . '|' . (string)filemtime($filePath));
        if ($this->cache !== null && $this->cache->has($cacheIdentifier)) {
            // PhpFrontend::require() evaluates the cached "return array(...)"
            // source (get() would return the raw source string). Unlike
            // requireOnce(), require() also works for repeated access to the
            // same entry within one request.
            $cached = $this->cache->require($cacheIdentifier);

            return is_array($cached) ? $cached : [];
        }

        $config = $this->parseFile($filePath);

        if ($this->cache !== null) {
            // PhpFrontend stores PHP source code, not values
            $this->cache->set($cacheIdentifier, 'return ' . var_export($config, true) . ';');
        }

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseFile(string $absolutePath): array
    {
        try {
            $parsed = Yaml::parseFile($absolutePath);
        } catch (\Throwable) {
            return [];
        }

        return is_array($parsed) ? $parsed : [];
    }

    private function resolveFilePath(string $contentBlockName): string
    {
        // ContentBlockRegistry is optional to keep the loader usable in
        // container-less contexts (unit tests); without it, headless.yaml
        // support is simply disabled.
        if ($this->contentBlockRegistry === null
            || !$this->contentBlockRegistry->hasContentBlock($contentBlockName)
        ) {
            return '';
        }

        $extPath = $this->contentBlockRegistry->getContentBlockExtPath($contentBlockName);
        $absolutePath = GeneralUtility::getFileAbsFileName($extPath . '/' . self::FILENAME);

        if ($absolutePath === '' || !is_file($absolutePath)) {
            return '';
        }

        return $absolutePath;
    }
}
