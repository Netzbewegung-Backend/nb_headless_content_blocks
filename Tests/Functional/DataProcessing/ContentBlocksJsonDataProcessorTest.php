<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Functional\DataProcessing;

use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ContentBlocksJsonDataProcessor;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ContentBlocksJsonDataProcessorTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/nb_headless_content_blocks/Tests/Fixtures/Extensions/test_nb_headless_content_blocks',
        'typo3conf/ext/content_blocks',
        'typo3conf/ext/nb_headless_content_blocks',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/simple_content_element.csv');
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

        $expected = (new \DateTimeImmutable('@1697810914'))->format(\DateTimeImmutable::W3C);
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
    public function processReturnsProcessedDataUnchangedForUnknownTable(): void
    {
        $row = ['uid' => 1];
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->start($row, 'tx_unknown_table');

        $subject = $this->get(ContentBlocksJsonDataProcessor::class);
        $processedData = ['data' => $row, 'other' => 'value'];
        $result = $subject->process($contentObjectRenderer, [], [], $processedData);

        self::assertSame($processedData, $result);
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
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->start($row, 'tt_content');

        return $contentObjectRenderer;
    }
}
