<?php
declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing;

use JsonSerializable;
use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\JsonSerializable\RecordJsonSerializable;
use stdClass;
use TYPO3\CMS\ContentBlocks\DataProcessing\ContentBlockDataDecorator;
use TYPO3\CMS\ContentBlocks\DataProcessing\ContentTypeResolver;
use TYPO3\CMS\ContentBlocks\Definition\ContentType\ContentTypeInterface;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\ContentBlocks\Registry\ContentBlockRegistry;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

readonly class ContentBlocksJsonDataProcessor implements DataProcessorInterface
{

    public function __construct(
        protected TableDefinitionCollection $tableDefinitionCollection,
        protected RecordFactory $recordFactory,
        protected ContentBlockDataDecorator $contentBlockDataDecorator,
        protected ContentTypeResolver $contentTypeResolver,
        protected ContentBlockRegistry $contentBlockRegistry,
    )
    {
        
    }

    public function process(
        ContentObjectRenderer $contentObjectRenderer,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array
    {
        $serverRequest = $contentObjectRenderer->getRequest();
        $this->contentBlockDataDecorator->setRequest($serverRequest);
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

        $data = new RecordJsonSerializable($resolveRecord, $tableDefinition, $this->tableDefinitionCollection);

        $data = $this->processDataWithLocalHeadlessPhp($data, $contentTypeDefinition);

        return [$as => $data];
    }

    private function processDataWithLocalHeadlessPhp(JsonSerializable $jsonSerializable, ContentTypeInterface $contentType): null|array|stdClass|JsonSerializable
    {
        $contentBlockExtPath = $this->contentBlockRegistry->getContentBlockExtPath($contentType->getName());

        $contentBlockExtPathAbsolute = GeneralUtility::getFileAbsFileName($contentBlockExtPath);

        $headlessPhpFile = $contentBlockExtPathAbsolute . '/headless.php';

        if (file_exists($headlessPhpFile)) {
            return $this->includeLocalHeadlessPhp($jsonSerializable, $headlessPhpFile);
        }

        return $jsonSerializable;
    }

    private function includeLocalHeadlessPhp(JsonSerializable $jsonSerializable, string $headlessPhpFile): null|array|stdClass|JsonSerializable
    {
        // Convert to simple format (arrays, objects, string, etc.)
        $data = json_decode(json_encode($jsonSerializable));

        return require $headlessPhpFile;
    }
}
