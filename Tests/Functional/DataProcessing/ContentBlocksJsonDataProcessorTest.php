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

    #[Test]
    public function processPassesPlainTextareaFieldValueThrough(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/textarea_content_element.csv');
        $row = $this->fetchContentRow(20);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [], ['data' => $row]);

        self::assertSame('Plain text content', $result['data']['my_textarea']);
    }

    #[Test]
    public function processConvertsColorFieldValueThrough(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/color_email_slug_content_element.csv');
        $row = $this->fetchContentRow(30);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [], ['data' => $row]);

        self::assertSame('#ff0000', $result['data']['my_color']);
    }

    #[Test]
    public function processConvertsEmailFieldValueThrough(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/color_email_slug_content_element.csv');
        $row = $this->fetchContentRow(30);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [], ['data' => $row]);

        self::assertSame('user@example.com', $result['data']['my_email']);
    }

    #[Test]
    public function processConvertsSlugFieldValueThrough(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/color_email_slug_content_element.csv');
        $row = $this->fetchContentRow(30);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [], ['data' => $row]);

        self::assertSame('my-page-slug', $result['data']['my_slug']);
    }

    #[Test]
    public function processReturnsNullForEmptyLinkValue(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/extended_simple_content_element.csv');
        $row = $this->fetchContentRow(3);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [], ['data' => $row]);

        self::assertNull($result['data']['my_link']);
    }

    #[Test]
    public function processAppliesAdditionalDataProcessors(): void
    {
        $row = $this->fetchContentRow(1);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [
            'dataProcessing.' => [
                '10' => 'test.set-rendered-content',
            ],
        ], ['data' => $row]);

        self::assertArrayHasKey('data', $result);
        self::assertArrayHasKey('renderedContent', $result['data']);
    }

    #[Test]
    public function processConvertsMultipleCategoriesToArray(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/extended_simple_content_element.csv');
        $row = $this->fetchContentRow(1);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [], ['data' => $row]);

        self::assertCount(3, $result['data']['my_categories']);
        $titles = array_column($result['data']['my_categories'], 'title');
        self::assertContains('Category one', $titles);
        self::assertContains('Category two', $titles);
        self::assertContains('Category three', $titles);
    }

    #[Test]
    public function processConvertsLinkWithTargetAndTitleToArray(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/extended_simple_content_element.csv');
        $row = $this->fetchContentRow(4);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [], ['data' => $row]);

        self::assertArrayHasKey('my_link', $result['data']);
        self::assertSame('https://example.com', $result['data']['my_link']['url']);
        self::assertSame('_blank', $result['data']['my_link']['target']);
        self::assertNotEmpty($result['data']['my_link']['title']);
    }

    #[Test]
    public function processReturnsGracefulResultForMissingFileReference(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/missing_file_reference_content_element.csv');
        $row = $this->fetchContentRow(40);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [], ['data' => $row]);

        self::assertArrayHasKey('my_image', $result['data']);
        self::assertIsArray($result['data']['my_image']);
        self::assertSame(99, $result['data']['my_image']['id']);
        self::assertEmpty($result['data']['my_image']['publicUrl']);
    }

    #[Test]
    public function processReturnsNullForNullDateTimeValue(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/extended_simple_content_element.csv');
        $row = $this->fetchContentRow(5);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [], ['data' => $row]);

        self::assertNull($result['data']['my_datetime']);
    }

    #[Test]
    public function processConvertsRichCollectionSubFieldsToArray(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/rich_collection_content_element.csv');
        $row = $this->fetchContentRow(50);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [], ['data' => $row]);

        self::assertArrayHasKey('my_items', $result['data']);
        self::assertIsArray($result['data']['my_items']);
        self::assertCount(3, $result['data']['my_items']);

        $titles = array_column($result['data']['my_items'], 'title');
        self::assertContains('First Item', $titles);
        self::assertContains('Second Item', $titles);
        self::assertContains('Third Item', $titles);

        $quantities = array_column($result['data']['my_items'], 'quantity');
        self::assertContains(10, $quantities);
        self::assertContains(25, $quantities);
        self::assertContains(0, $quantities);

        $notes = array_column($result['data']['my_items'], 'note');
        self::assertContains('First note', $notes);
        self::assertContains('Second note', $notes);
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
