<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Functional\Frontend;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * End-to-end tests: a full frontend request against a headless site whose
 * Content Blocks are mapped via lib.contentBlock (EXT:headless page JSON
 * wrapper + nb-content-blocks-json data processor).
 *
 * Freezes the complete JSON output of the e2e fixture page, so changes in
 * the interplay of EXT:headless and this extension become visible.
 *
 * Deprecations are ignored because loading EXT:headless in a TYPO3 14.3
 * test instance triggers core deprecation-108345 (ext_emconf.php shipped
 * by the extension) — not something this extension can fix.
 *
 * @see https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/issues/18
 */
#[IgnoreDeprecations]
final class ContentBlocksJsonResponseTest extends FunctionalTestCase
{
    private const SITE_IDENTIFIER = 'e2e-test';

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/nb_headless_content_blocks/Tests/Fixtures/Extensions/test_nb_headless_content_blocks',
        'typo3conf/ext/container',
        'typo3conf/ext/content_blocks',
        'typo3conf/ext/headless',
        'typo3conf/ext/nb_headless_content_blocks',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3conf/ext/nb_headless_content_blocks/Tests/Functional/DataProcessing/Fixtures/Files/' => 'fileadmin/',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/e2e_page.csv');
        $this->importCSVDataSet(__DIR__ . '/../DataProcessing/Fixtures/DataSet/simple_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/../DataProcessing/Fixtures/DataSet/headless_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/../DataProcessing/Fixtures/DataSet/file_reference_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/../DataProcessing/Fixtures/DataSet/textarea_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/../DataProcessing/Fixtures/DataSet/color_email_slug_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/../DataProcessing/Fixtures/DataSet/rich_collection_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/../DataProcessing/Fixtures/DataSet/richtext_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/e2e_richtext_link.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/e2e_container.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/e2e_container_block.csv');
        $this->writeSiteConfiguration();

        // EXT:headless selects tt_content ordered by "sorting" only. Give the
        // imported rows a deterministic order (all fixture rows have sorting = 0).
        $this->get(ConnectionPool::class)->getConnectionForTable('tt_content')
            ->executeStatement('UPDATE tt_content SET sorting = uid * 10');
    }

    public function tearDown(): void
    {
        unset($_SERVER['HTTPS']);
        parent::tearDown();
    }

    #[Test]
    public function pageIsDeliveredAsJsonResponse(): void
    {
        $response = $this->executeFrontendSubRequest($this->frontendRequest());

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));

        $json = $this->decodeJsonResponse($response);

        // Page level wrapper delivered by EXT:headless
        self::assertSame(1, $json['id']);
        self::assertSame('Standard', $json['type']);
        self::assertSame('/', $json['slug']);
        self::assertSame('E2E Test Page', $json['meta']['title']);
        self::assertSame('E2E Test Page', $json['seo']['title']);
    }

    #[Test]
    public function allContentBlocksAreRenderedAsJson(): void
    {
        $response = $this->executeFrontendSubRequest($this->frontendRequest());

        $json = $this->decodeJsonResponse($response);

        self::assertSame([
            $this->contentElement(1, 'test_simple', [
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
            ]),
            $this->contentElement(2, 'test_headless', [
                'header' => 'HeaderHeadless',
                'my_text' => 'MODIFY ME',
                'headless_processed' => true,
            ]),
            $this->contentElement(10, 'test_filetest', [
                'header' => 'FileTestHeader',
                'my_image' => [
                    'id' => 1,
                    'alt' => '',
                    'title' => '',
                    'publicUrl' => 'https://example.com/fileadmin/test-image.jpg',
                    'thumbnails' => [
                        'mobile' => 'https://example.com/fileadmin/_processed_/2/d/csm_test-image_3dd7363be1.jpg',
                        'desktop' => 'https://example.com/fileadmin/_processed_/2/d/csm_test-image_269109a1a1.jpg',
                    ],
                ],
                'my_images' => [
                    [
                        'id' => 2,
                        'alt' => '',
                        'title' => '',
                        'publicUrl' => 'https://example.com/fileadmin/test-image.jpg',
                        'thumbnails' => [
                            'mobile' => 'https://example.com/fileadmin/_processed_/2/d/csm_test-image_424434f26d.jpg',
                        ],
                    ],
                    [
                        'id' => 3,
                        'alt' => '',
                        'title' => '',
                        'publicUrl' => 'https://example.com/fileadmin/test-image.jpg',
                        'thumbnails' => [
                            'mobile' => 'https://example.com/fileadmin/_processed_/2/d/csm_test-image_424434f26d.jpg',
                        ],
                    ],
                ],
            ]),
            $this->contentElement(20, 'test_textarea', [
                'header' => 'HeaderTextarea',
                'my_textarea' => 'Plain text content',
            ]),
            $this->contentElement(30, 'test_coloremailslug', [
                'header' => 'HeaderColorEmailSlug',
                'my_color' => '#ff0000',
                'my_email' => 'user@example.com',
                'my_slug' => 'my-page-slug',
            ]),
            $this->contentElement(50, 'test_collectiontest', [
                'header' => 'RichCollectionTest',
                'my_items' => [
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
                ],
            ]),
            $this->contentElement(60, 'test_richtext', [
                'header' => 'HeaderRichtext',
                'my_richtext' => '<p>This is <strong>rich</strong> text</p>',
            ]),
            $this->contentElement(61, 'test_richtext', [
                'header' => 'HeaderRichtextLink',
                // t3:// link URI from a real production record, resolved to
                // the target page URL by parseFunc's a-tag typolink handler
                'my_richtext' => '<p>Test Test Test <a href="/link-target">Link zur Projekten</a> Test Test Test</p>',
            ]),
            // b13/container element: children columns (nb-container-json)
            // alongside the headless wrapper, children kept out of the
            // regular page content by the colPos exclusion in lib.content
            [
                'id' => 70,
                'type' => 'test_2cols_container',
                'colPos' => 0,
                'categories' => '',
                'appearance' => [
                    'layout' => 'default',
                    'frameClass' => 'default',
                    'spaceBefore' => '',
                    'spaceAfter' => '',
                ],
                'left' => [
                    $this->containerChild(71, 201, 'ChildLeft', 'Left child content'),
                ],
                'right' => [
                    $this->containerChild(72, 202, 'ChildRight', 'Right child content'),
                ],
            ],
            // Container as Content Block (docs variant 2, production pattern):
            // own fields AND the children columns nested inside "data"
            $this->contentElement(80, 'test_containerblock', [
                'header' => 'Container Block',
                'my_text' => 'Own container data',
                'left' => [
                    $this->containerChild(81, 211, 'ChildBlockLeft', 'Block left child content'),
                ],
                'right' => [
                    $this->containerChild(82, 212, 'ChildBlockRight', 'Block right child content'),
                ],
            ]),
        ], $json['content']['colPos0']);
    }

    /**
     * Frontend request to the e2e page. On TYPO3 13, absolute public URLs
     * are built via GeneralUtility::getIndpEnv(), which reads $_SERVER
     * (the testing framework only fills $_SERVER['HTTP_HOST'] there) —
     * $_SERVER['HTTPS'] is therefore set to keep the absolute URL prefix
     * "https://example.com/" identical on all supported TYPO3 versions.
     */
    private function frontendRequest(): InternalRequest
    {
        $_SERVER['HTTPS'] = 'on';

        return (new InternalRequest('https://example.com/'))
            ->withServerParams([
                'SCRIPT_NAME' => '/index.php',
                'HTTP_HOST' => 'example.com',
                'SERVER_NAME' => 'example.com',
                'HTTPS' => 'on',
                'REMOTE_ADDR' => '127.0.0.1',
            ])
            ->withPageId(1);
    }

    /**
     * A container child rendered through lib.contentBlock (full headless
     * wrapper + data), nested in the container's column.
     *
     * @return array<string, mixed>
     */
    private function containerChild(int $id, int $colPos, string $header, string $textarea): array
    {
        return [
            'id' => $id,
            'type' => 'test_textarea',
            'colPos' => $colPos,
            'categories' => '',
            'appearance' => [
                'layout' => 'default',
                'frameClass' => 'default',
                'spaceBefore' => '',
                'spaceAfter' => '',
            ],
            'data' => [
                'header' => $header,
                'my_textarea' => $textarea,
            ],
        ];
    }

    /**
     * The content element wrapper delivered by EXT:headless'
     * lib.contentElement around the "data" produced by this extension.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function contentElement(int $id, string $type, array $data): array
    {
        return [
            'id' => $id,
            'type' => $type,
            'colPos' => 0,
            'categories' => '',
            'appearance' => [
                'layout' => 'default',
                'frameClass' => 'default',
                'spaceBefore' => '',
                'spaceAfter' => '',
            ],
            'data' => $data,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonResponse(ResponseInterface $response): array
    {
        $json = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($json);

        return $json;
    }

    private function writeSiteConfiguration(): void
    {
        $sitePath = $this->instancePath . '/typo3conf/sites/' . self::SITE_IDENTIFIER;
        GeneralUtility::mkdir_deep($sitePath);
        GeneralUtility::writeFile($sitePath . '/config.yaml', <<<'YAML'
rootPageId: 1
base: 'https://example.com/'
dependencies:
  - test_nb_headless_content_blocks/test-frontend
languages:
  -
    title: English
    enabled: true
    languageId: 0
    base: /
    locale: en_US.UTF-8
    navigationTitle: English
    flag: us
YAML);
    }
}
