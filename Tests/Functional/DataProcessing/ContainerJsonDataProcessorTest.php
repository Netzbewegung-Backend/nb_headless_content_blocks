<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Functional\DataProcessing;

use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ContainerJsonDataProcessor;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ContainerJsonDataProcessorTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/nb_headless_content_blocks/Tests/Fixtures/Extensions/test_nb_headless_content_blocks',
        'typo3conf/ext/container',
        'typo3conf/ext/content_blocks',
        'typo3conf/ext/nb_headless_content_blocks',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/container_content_element.csv');
    }

    #[Test]
    public function processExtractsRenderedContentOfLeftColumn(): void
    {
        $row = $this->fetchContentRow(10);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContainerJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [
            'colPos' => 201,
            'as' => 'left',
            'skipRenderingChildContent' => 1,
            'dataProcessing.' => [
                '10' => 'test.set-rendered-content',
            ],
        ], ['data' => $row]);

        self::assertSame(['STUB:ChildLeft'], $result['left']);
    }

    #[Test]
    public function processExtractsRenderedContentOfRightColumn(): void
    {
        $row = $this->fetchContentRow(10);
        $contentObjectRenderer = $this->createContentObjectRenderer($row);

        $subject = $this->get(ContainerJsonDataProcessor::class);
        $result = $subject->process($contentObjectRenderer, [], [
            'colPos' => 202,
            'as' => 'right',
            'skipRenderingChildContent' => 1,
            'dataProcessing.' => [
                '10' => 'test.set-rendered-content',
            ],
        ], ['data' => $row]);

        self::assertSame(['STUB:ChildRight'], $result['right']);
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
        $request = new ServerRequest('https://example.com/', 'GET');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $contentObjectRenderer->start($row, 'tt_content');

        return $contentObjectRenderer;
    }
}
