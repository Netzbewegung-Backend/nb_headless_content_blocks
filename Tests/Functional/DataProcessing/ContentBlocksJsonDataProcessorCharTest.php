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

/**
 * Characterization tests: freeze the complete JSON contract of the
 * ToArray conversion for every fixture record, so that the planned
 * rewrite (see docs/design/IMPROVE_TO_ARRAY.md) cannot change the
 * output unnoticed.
 */
final class ContentBlocksJsonDataProcessorCharTest extends FunctionalTestCase
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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/textarea_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/color_email_slug_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/rich_collection_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/richtext_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/cropped_file_reference_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/non_content_block_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/folder_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/flexform_content_element.csv');
    }

    #[Test]
    public function simpleBlock(): void
    {
        self::assertSame([
            'bodytext' => 'BodytextSimple',
            'header' => 'HeaderSimple',
            'my_categories' => [
                [
                    'uid' => 1,
                    'pid' => 1,
                    'title' => 'Category one',
                ],
            ],
            'my_collection' => [
                [
                    'text' => 'Collection item one',
                ],
                [
                    'text' => 'Collection item two',
                ],
            ],
            'my_datetime' => '2023-10-20T14:08:34+00:00',
            'my_json' => ['a' => 1],
            'my_link' => [
                'url' => 'https://example.com',
                'target' => '',
                'type' => 'url',
                'title' => 'https://example.com',
                'config' => [
                    'parameter' => 'https://example.com',
                ],
                'attr' => ['href' => 'https://example.com'],
            ],
            'my_number' => 42,
            'my_password' => '',
            'my_select' => 'one',
            'my_text' => 'MyTextSimple',
        ], $this->processRow(1)['data']);
    }

    #[Test]
    public function emptyValuesBlock(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/extended_simple_content_element.csv');
        self::assertSame([
            'bodytext' => null,
            'header' => 'EmptyLink',
            'my_categories' => [],
            'my_collection' => [],
            'my_datetime' => null,
            'my_json' => null,
            'my_link' => null,
            'my_number' => 0,
            'my_password' => '',
            'my_select' => '',
            'my_text' => '',
        ], $this->processRow(3)['data']);
    }

    #[Test]
    public function linkWithTargetAndTitleBlock(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/extended_simple_content_element.csv');
        $data = $this->processRow(4)['data'];

        self::assertSame('https://example.com', $data['my_link']['url']);
        self::assertSame('_blank', $data['my_link']['target']);
        self::assertSame('url', $data['my_link']['type']);
        // Title falls back to the URL when no link text is set: the typolink
        // title itself is only accessible in config / attr.
        self::assertSame('https://example.com', $data['my_link']['title']);
        self::assertSame('https://example.com _blank \\"Link Title\\"', $data['my_link']['config']['parameter']);
        self::assertSame('https://example.com', $data['my_link']['attr']['href']);
        self::assertSame('_blank', $data['my_link']['attr']['target']);
        self::assertSame('noreferrer', $data['my_link']['attr']['rel']);
        // TYPO3's typolink parser mis-splits the quoted title, keep actual behavior frozen:
        self::assertSame('Title"', $data['my_link']['attr']['title']);
        self::assertSame('"Link', $data['my_link']['attr']['class']);
    }

    #[Test]
    public function nullDateTimeBlock(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/extended_simple_content_element.csv');
        self::assertNull($this->processRow(5)['data']['my_datetime']);
    }

    #[Test]
    public function dateTimeFormatOptionChangesOutput(): void
    {
        $data = $this->processRow(1, processorConfiguration: [
            'options.' => [
                'dateTimeFormat' => 'Y',
            ],
        ])['data'];

        self::assertSame('2023', $data['my_datetime']);
    }

    #[Test]
    public function headlessPhpBlock(): void
    {
        self::assertSame([
            'header' => 'HeaderHeadless',
            'my_text' => 'MODIFY ME',
            'headless_processed' => true,
        ], $this->processRow(2)['data']);
    }

    #[Test]
    public function fileTestBlock(): void
    {
        $data = $this->processRow(10)['data'];

        self::assertSame('FileTestHeader', $data['header']);
        self::assertSame(1, $data['my_image']['id']);
        self::assertSame('', $data['my_image']['alt']);
        self::assertSame('', $data['my_image']['title']);
        self::assertStringEndsWith('/fileadmin/test-image.jpg', $data['my_image']['publicUrl']);

        self::assertSame([2, 3], array_map(
            static fn(array $image): int => $image['id'],
            $data['my_images']
        ));
        foreach ($data['my_images'] as $image) {
            self::assertStringEndsWith('/fileadmin/test-image.jpg', $image['publicUrl']);
        }
    }

    #[Test]
    public function croppedFileTestBlock(): void
    {
        $data = $this->processRow(11)['data'];

        self::assertSame(4, $data['my_image']['id']);
        self::assertStringContainsString('_processed_', $data['my_image']['publicUrl']);
    }

    #[Test]
    public function fileTestBlockWithHeadlessYamlThumbnails(): void
    {
        $data = $this->processRow(10)['data'];

        self::assertArrayHasKey('thumbnails', $data['my_image']);
        self::assertStringContainsString('_processed_', $data['my_image']['thumbnails']['mobile']);
        self::assertStringContainsString('_processed_', $data['my_image']['thumbnails']['desktop']);

        foreach ($data['my_images'] as $image) {
            self::assertArrayHasKey('thumbnails', $image);
            self::assertStringContainsString('_processed_', $image['thumbnails']['mobile']);
        }
    }

    #[Test]
    public function fileTestBlockWithTypoScriptProcessingOverride(): void
    {
        $data = $this->processRow(10, processorConfiguration: [
            'options.' => [
                'processing.' => [
                    'my_image.' => [
                        'mobile' => 'width=150c,fileExtension=jpg',
                    ],
                ],
            ],
        ])['data'];

        self::assertStringContainsString('_processed_', $data['my_image']['thumbnails']['mobile']);
        // headless.yaml desktop variant is kept, mobile overridden (merge)
        self::assertArrayHasKey('desktop', $data['my_image']['thumbnails']);
        // TypoScript can add variants that headless.yaml does not define
        // (proves the override is actually applied, not just vacuously green)
        self::assertArrayNotHasKey('xl', $data['my_image']['thumbnails']);
        self::assertSame(
            $data['my_image']['thumbnails']['desktop'],
            $this->processRow(10)['data']['my_image']['thumbnails']['desktop']
        );
    }

    #[Test]
    public function fileTestBlockWithTypoScriptProcessingOverrideAddsVariant(): void
    {
        $data = $this->processRow(10, processorConfiguration: [
            'options.' => [
                'processing.' => [
                    'my_image.' => [
                        'xl' => 'width=350c,fileExtension=jpg',
                    ],
                ],
            ],
        ])['data'];

        self::assertStringContainsString('_processed_', $data['my_image']['thumbnails']['xl']);
        // headless.yaml variants survive next to the added one
        self::assertArrayHasKey('mobile', $data['my_image']['thumbnails']);
        self::assertArrayHasKey('desktop', $data['my_image']['thumbnails']);
    }

    #[Test]
    public function textareaBlock(): void
    {
        self::assertSame([
            'header' => 'HeaderTextarea',
            'my_textarea' => 'Plain text content',
        ], $this->processRow(20)['data']);
    }

    #[Test]
    public function colorEmailSlugBlock(): void
    {
        self::assertSame([
            'header' => 'HeaderColorEmailSlug',
            'my_color' => '#ff0000',
            'my_email' => 'user@example.com',
            'my_slug' => 'my-page-slug',
        ], $this->processRow(30)['data']);
    }

    #[Test]
    public function richtextBlock(): void
    {
        self::assertSame(
            '<p>This is <strong>rich</strong> text</p>',
            $this->processRow(60, withRichTextSetup: true)['data']['my_richtext']
        );
    }

    #[Test]
    public function richCollectionBlock(): void
    {
        self::assertSame([
            [
                'note' => 'First note',
                'quantity' => 10,
                'title' => 'First Item',
            ],
            [
                'note' => 'Second note',
                'quantity' => 25,
                'title' => 'Second Item',
            ],
            [
                'note' => '',
                'quantity' => 0,
                'title' => 'Third Item',
            ],
        ], $this->processRow(50)['data']['my_items']);
    }

    #[Test]
    public function multipleCategoriesBlock(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/extended_simple_content_element.csv');
        $data = $this->processRow(1)['data'];

        self::assertSame([
            ['uid' => 1, 'pid' => 1, 'title' => 'Category one'],
            ['uid' => 2, 'pid' => 1, 'title' => 'Category two'],
            ['uid' => 3, 'pid' => 1, 'title' => 'Category three'],
        ], $data['my_categories']);
    }

    #[Test]
    public function nonContentBlockRecord(): void
    {
        $result = $this->processRow(70);

        self::assertInstanceOf(\TYPO3\CMS\Core\Domain\Record::class, $result['data']);
    }

    #[Test]
    public function folderBlock(): void
    {
        self::assertSame([
            'header' => 'HeaderFolder',
            'my_folder' => [
                '/fileadmin/test-folder/',
            ],
        ], $this->processRow(90)['data']);
    }

    #[Test]
    public function flexformBlock(): void
    {
        self::assertSame([
            'header' => 'HeaderFlexform',
            'my_flexform' => [
                'sDEF' => [
                    'my_text' => 'FlexValue',
                ],
            ],
        ], $this->processRow(91)['data']);
    }

    /**
     * @return array<string, mixed>
     */
    private function processRow(int $uid, bool $extended = false, bool $withRichTextSetup = false, array $processorConfiguration = []): array
    {
        $row = $this->get(ConnectionPool::class)->getQueryBuilderForTable('tt_content')
            ->select('*')->from('tt_content')
            ->where('uid=' . $uid)->executeQuery()->fetchAssociative();

        $request = (new ServerRequest('https://example.com/', 'GET'))
            ->withAttribute('applicationType', 1);

        if ($withRichTextSetup) {
            $frontendTypoScript = new \TYPO3\CMS\Core\TypoScript\FrontendTypoScript(
                new \TYPO3\CMS\Core\TypoScript\AST\Node\RootNode(),
                [],
                [],
                []
            );
            $frontendTypoScript->setSetupTree(new \TYPO3\CMS\Core\TypoScript\AST\Node\RootNode());
            $frontendTypoScript->setSetupArray([
                'lib.' => [
                    'parseFunc_RTE' => '1',
                    'parseFunc_RTE.' => [
                        'allowTags' => 'p,strong,em',
                        'htmlSanitize' => '0',
                    ],
                ],
            ]);
            $request = $request->withAttribute('frontend.typoscript', $frontendTypoScript);
        }

        $GLOBALS['TYPO3_REQUEST'] = $request;

        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $contentObjectRenderer->start($row, 'tt_content');
        GeneralUtility::addInstance(ContentObjectRenderer::class, $contentObjectRenderer);

        return $this->get(ContentBlocksJsonDataProcessor::class)
            ->process($contentObjectRenderer, [], $processorConfiguration, ['data' => $row]);
    }
}
