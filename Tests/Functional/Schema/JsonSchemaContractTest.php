<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Functional\Schema;

use JsonSchema\Validator;
use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ContentBlocksJsonDataProcessor;
use Netzbewegung\NbHeadlessContentBlocks\Schema\JsonSchemaGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class JsonSchemaContractTest extends FunctionalTestCase
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
        $this->importCSVDataSet(__DIR__ . '/../DataProcessing/Fixtures/DataSet/simple_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/../DataProcessing/Fixtures/DataSet/extended_simple_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/../DataProcessing/Fixtures/DataSet/headless_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/../DataProcessing/Fixtures/DataSet/file_reference_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/../DataProcessing/Fixtures/DataSet/textarea_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/../DataProcessing/Fixtures/DataSet/color_email_slug_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/../DataProcessing/Fixtures/DataSet/rich_collection_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/../DataProcessing/Fixtures/DataSet/richtext_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/../DataProcessing/Fixtures/DataSet/folder_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/../DataProcessing/Fixtures/DataSet/flexform_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/../DataProcessing/Fixtures/DataSet/yamledges_content_element.csv');
        $this->importCSVDataSet(__DIR__ . '/../DataProcessing/Fixtures/DataSet/yamlbroken_content_element.csv');
    }

    public static function contentBlockRecordProvider(): \Generator
    {
        yield 'simple block' => [1, 'test_simple', false];
        yield 'headless.php block' => [2, 'test_headless', false];
        yield 'simple block with empty values' => [3, 'test_simple', false];
        yield 'simple block with link target' => [4, 'test_simple', false];
        yield 'file block' => [10, 'test_filetest', false];
        yield 'textarea block' => [20, 'test_textarea', false];
        yield 'color email slug block' => [30, 'test_coloremailslug', false];
        yield 'collection block' => [50, 'test_collectiontest', false];
        yield 'richtext block' => [60, 'test_richtext', true];
        yield 'folder block' => [90, 'test_foldertest', false];
        yield 'flexform block' => [91, 'test_flexformtest', false];
        yield 'yaml edges block' => [92, 'test_yamledges', false];
        yield 'broken yaml block' => [93, 'test_yamlbroken', false];
    }

    #[DataProvider('contentBlockRecordProvider')]
    #[Test]
    public function processorOutputMatchesGeneratedSchema(int $uid, string $typeName, bool $withRichTextSetup): void
    {
        $schema = $this->get(JsonSchemaGenerator::class)->generateForTypeName($typeName);
        self::assertNotNull($schema, 'No schema generated for ' . $typeName);

        $data = $this->processRow($uid, $withRichTextSetup)['data'];

        $dataObject = json_decode((string)json_encode($data));
        $schemaObject = json_decode((string)json_encode($schema));

        $validator = new Validator();
        $validator->validate($dataObject, $schemaObject);

        self::assertTrue(
            $validator->isValid(),
            sprintf(
                'Record %d (%s) does not match the generated schema: %s',
                $uid,
                $typeName,
                (string)json_encode($validator->getErrors())
            )
        );
    }

    #[Test]
    public function missingFileStillMatchesSchema(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../DataProcessing/Fixtures/DataSet/missing_file_reference_content_element.csv');

        $schema = $this->get(JsonSchemaGenerator::class)->generateForTypeName('test_filetest');
        $data = $this->processRow(40, false)['data'];

        self::assertIsArray($data['my_image']);

        $dataObject = json_decode((string)json_encode($data));
        $schemaObject = json_decode((string)json_encode($schema));

        $validator = new Validator();
        $validator->validate($dataObject, $schemaObject);

        self::assertTrue(
            $validator->isValid(),
            (string)json_encode($validator->getErrors())
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function processRow(int $uid, bool $withRichTextSetup = false, array $processorConfiguration = []): array
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
