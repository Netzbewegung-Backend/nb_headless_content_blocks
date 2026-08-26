<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\ContentBlocks;

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Loads per-Content-Block image processing definitions from an optional
 * headless.yaml file next to the Content Block config.yaml:
 *
 *     fields:
 *       image:
 *         processing:
 *           mobile: "width=883c,fileExtension=webp"
 *           desktop: "width=1564c,fileExtension=webp"
 *
 * Values follow the ext:headless ProcessingConfiguration option syntax
 * ("key=value" pairs, comma separated).
 */
final class HeadlessYamlLoader
{
    private const FILENAME = 'headless.yaml';

    public function __construct(
        private readonly ?FrontendInterface $cache = null,
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
     * @return array<string, mixed>
     */
    private function loadConfig(string $contentBlockName): array
    {
        $cacheIdentifier = 'headless_yaml_' . md5($contentBlockName);
        if ($this->cache !== null && $this->cache->has($cacheIdentifier)) {
            return $this->cache->get($cacheIdentifier);
        }

        $config = $this->parseFile($contentBlockName);

        if ($this->cache !== null) {
            $this->cache->set($cacheIdentifier, $config);
        }

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseFile(string $contentBlockName): array
    {
        $extPath = $this->getContentBlockExtPath($contentBlockName);
        if ($extPath === null) {
            return [];
        }

        $absolutePath = GeneralUtility::getFileAbsFileName($extPath . '/' . self::FILENAME);
        if ($absolutePath === '' || !is_file($absolutePath)) {
            return [];
        }

        try {
            $parsed = Yaml::parseFile($absolutePath);
        } catch (\Throwable) {
            return [];
        }

        return is_array($parsed) ? $parsed : [];
    }

    private function getContentBlockExtPath(string $contentBlockName): ?string
    {
        $contentBlockRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\ContentBlocks\Registry\ContentBlockRegistry::class
        );

        if (!$contentBlockRegistry->hasContentBlock($contentBlockName)) {
            return null;
        }

        return $contentBlockRegistry->getContentBlockExtPath($contentBlockName);
    }
}
