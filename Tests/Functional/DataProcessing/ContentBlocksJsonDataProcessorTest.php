<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Functional\DataProcessing;

use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ContentBlocksJsonDataProcessor;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ContentBlocksJsonDataProcessorTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/nb_headless_content_blocks/Tests/Fixtures/Extensions/test_nb_headless_content_blocks',
        'typo3conf/ext/container',
        'typo3conf/ext/content_blocks',
        'typo3conf/ext/nb_headless_content_blocks',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3conf/ext/nb_headless_content_blocks/Tests/Functional/DataProcessing/Fixtures/Files/' => 'fileadmin/',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/simple_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/headless_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/file_reference_content_element.csv');
    }

    #[Test]
    public function processConvertsContentBlockRecordToArray(): void
    {
        $row = $this->fetchContentRow(1);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [], ['data' => $row]);

        self::assertArrayHasKey('data', $result);
        self::assertSame('HeaderSimple', $result['data']['header']);
        self::assertSame('BodytextSimple', $result['data']['bodytext']);
        self::assertSame('MyTextSimple', $result['data']['my_text']);
    }

    #[Test]
    public function processRemovesSystemFieldsFromResult(): void
    {
        $row = $this->fetchContentRow(1);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [], ['data' => $row]);

        self::assertArrayNotHasKey('uid', $result['data']);
        self::assertArrayNotHasKey('pid', $result['data']);
        self::assertArrayNotHasKey('colPos', $result['data']);
        self::assertArrayNotHasKey('CType', $result['data']);
    }

    #[Test]
    public function processUsesCustomAsKey(): void
    {
        $row = $this->fetchContentRow(1);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], ['as' => 'content'], ['data' => $row]);

        self::assertArrayHasKey('content', $result);
        self::assertArrayNotHasKey('data', $result);
        self::assertSame('HeaderSimple', $result['content']['header']);
    }

    #[Test]
    public function processConvertsNumberFieldToInteger(): void
    {
        $row = $this->fetchContentRow(1);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [], ['data' => $row]);

        self::assertSame(42, $result['data']['my_number']);
    }

    #[Test]
    public function processConvertsDateTimeFieldToW3CString(): void
    {
        $row = $this->fetchContentRow(1);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [], ['data' => $row]);

        $expected = (new \DateTimeImmutable())->setTimestamp(1697810914)->format(\DateTimeImmutable::W3C);
        self::assertSame($expected, $result['data']['my_datetime']);
    }

    #[Test]
    public function processPassesSelectFieldValueThrough(): void
    {
        $row = $this->fetchContentRow(1);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [], ['data' => $row]);

        self::assertSame('one', $result['data']['my_select']);
    }

    #[Test]
    public function processEmptiesPasswordFieldValue(): void
    {
        $row = $this->fetchContentRow(1);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [], ['data' => $row]);

        self::assertSame('', $result['data']['my_password']);
    }

    #[Test]
    public function processPassesJsonFieldValueThroughAsArray(): void
    {
        $row = $this->fetchContentRow(1);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [], ['data' => $row]);

        self::assertSame(['a' => 1], $result['data']['my_json']);
    }

    #[Test]
    public function processConvertsLinkFieldToArray(): void
    {
        $row = $this->fetchContentRow(1);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [], ['data' => $row]);

        self::assertSame('https://example.com', $result['data']['my_link']['url']);
        self::assertSame('url', $result['data']['my_link']['type']);
    }

    #[Test]
    public function processConvertsCategoriesToReducedArray(): void
    {
        $row = $this->fetchContentRow(1);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [], ['data' => $row]);

        self::assertCount(1, $result['data']['my_categories']);
        $category = reset($result['data']['my_categories']);
        self::assertSame(1, $category['uid']);
        self::assertSame('Category one', $category['title']);
    }

    #[Test]
    public function processConvertsCollectionToRecordArray(): void
    {
        $row = $this->fetchContentRow(1);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [], ['data' => $row]);

        self::assertCount(2, $result['data']['my_collection']);
        self::assertContains('Collection item one', array_column($result['data']['my_collection'], 'text'));
        self::assertContains('Collection item two', array_column($result['data']['my_collection'], 'text'));
    }

    #[Test]
    public function processAppliesLocalHeadlessPhp(): void
    {
        $row = $this->fetchContentRow(2);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [], ['data' => $row]);

        self::assertSame('MODIFY ME', $result['data']['my_text']);
        self::assertTrue($result['data']['headless_processed']);
    }

    #[Test]
    public function processReturnsProcessedDataUnchangedForUnknownTable(): void
    {
        $row = ['uid' => 1];
        $request = (new ServerRequest('https://example.com/', 'GET'))
            ->withAttribute('applicationType', 1);
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $contentObjectRenderer->start($row, 'tx_unknown_table');

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $processedData = ['data' => $row, 'other' => 'value'];
        $result = $subject->process($contentObjectRenderer, [], [], $processedData);

        self::assertSame($processedData, $result);
    }

    #[Test]
    public function processConvertsFileReferenceOneToOneToArray(): void
    {
        $row = $this->fetchContentRow(10);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [], ['data' => $row]);

        self::assertArrayHasKey('data', $result);
        self::assertArrayHasKey('my_image', $result['data']);
        self::assertIsArray($result['data']['my_image']);
        self::assertSame(1, $result['data']['my_image']['id']);
        self::assertNotEmpty($result['data']['my_image']['publicUrl']);
    }

    #[Test]
    public function processConvertsFileReferenceOneToManyToArray(): void
    {
        $row = $this->fetchContentRow(10);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [], ['data' => $row]);

        self::assertArrayHasKey('data', $result);
        self::assertArrayHasKey('my_images', $result['data']);
        self::assertIsArray($result['data']['my_images']);
        self::assertCount(2, $result['data']['my_images']);
        self::assertSame(2, $result['data']['my_images'][0]['id']);
        self::assertSame(3, $result['data']['my_images'][1]['id']);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchContentRow(int $uid): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('tt_content');

        return $queryBuilder->select('*')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', $uid))
            ->executeQuery()
            ->fetchAssociative();
    }

    /**
     * @param array<string, mixed> $row
     */
    private function createContentObjectRenderer(array $row): ContentObjectRenderer
    {
        $request = (new ServerRequest('https://example.com/', 'GET'))
            ->withAttribute('applicationType', 1);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $contentObjectRenderer->start($row, 'tt_content');

        return $contentObjectRenderer;
    }
}
