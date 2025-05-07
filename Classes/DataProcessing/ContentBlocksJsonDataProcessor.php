<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing;

use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray\RecordToArray;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\ContentBlocks\DataProcessing\ContentTypeResolver;
use TYPO3\CMS\ContentBlocks\Definition\ContentType\ContentTypeInterface;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\ContentBlocks\Registry\ContentBlockRegistry;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

readonly class ContentBlocksJsonDataProcessor implements DataProcessorInterface
{
    public function __construct(
        protected TableDefinitionCollection $tableDefinitionCollection,
        protected RecordFactory $recordFactory,
        protected ContentTypeResolver $contentTypeResolver,
        protected ContentBlockRegistry $contentBlockRegistry,
    ) {

    }

    public function process(
        ContentObjectRenderer $contentObjectRenderer,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $table = $contentObjectRenderer->getCurrentTable();
        if (!$this->tableDefinitionCollection->hasTable($table)) {
            return $processedData;
        }

        $resolveRecord = $this->recordFactory->createResolvedRecordFromDatabaseRow(
            $table,
            $processedData['data'],
        );

        $contentTypeDefinition = $this->contentTypeResolver->resolve($resolveRecord);

        if (!$contentTypeDefinition instanceof ContentTypeInterface) {
            $processedData['data'] = $resolveRecord;
            return $processedData;
        }

        $as = $processorConfiguration['as'] ?? 'data';

        $tableDefinition = $this->tableDefinitionCollection->getTable($resolveRecord->getMainType());

        $data = GeneralUtility::makeInstance(RecordToArray::class, $resolveRecord, $tableDefinition, $this->tableDefinitionCollection)->toArray();

        $data = $this->processDataWithLocalHeadlessPhp($data, $contentTypeDefinition);

        // Process additional DataProcessors
        if (isset($processorConfiguration['dataProcessing.']) && is_array($processorConfiguration['dataProcessing.'])) {
            $additionalData = $this->processAdditionalDataProcessors($contentObjectRenderer, $processedData, $processorConfiguration, $contentObjectRenderer->getRequest());

            unset($additionalData['data']);
            unset($additionalData['current']);

            $data = array_merge($data, $additionalData);
        }

        return [$as => $data];
    }

    protected function processDataWithLocalHeadlessPhp(array $data, ContentTypeInterface $contentType): array
    {
        $contentBlockExtPath = $this->contentBlockRegistry->getContentBlockExtPath($contentType->getName());

        $contentBlockExtPathAbsolute = GeneralUtility::getFileAbsFileName($contentBlockExtPath);

        $headlessPhpFile = $contentBlockExtPathAbsolute . '/headless.php';

        if (file_exists($headlessPhpFile)) {
            return $this->includeLocalHeadlessPhp($data, $headlessPhpFile);
        }

        return $data;
    }

    protected function includeLocalHeadlessPhp(array $data, string $headlessPhpFile): array
    {
        return require $headlessPhpFile;
    }

    protected function processAdditionalDataProcessors(ContentObjectRenderer $contentObjectRenderer, array $data, array $processorConfiguration, ServerRequestInterface $request): array
    {
        return GeneralUtility::makeInstance(ContentDataProcessor::class)->process(
            $contentObjectRenderer,
            $processorConfiguration,
            $data
        );
    }
}
