<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Functional\Frontend;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * End-to-end tests of the JSON Schema endpoint middleware with the
 * endpoint explicitly enabled via the site setting
 * "schemaEndpoint.enabled" (functional tests run in the "Testing"
 * application context, which is not Development, so only the setting
 * can grant access here).
 *
 * Freezes the delivery contract of the endpoint response: status code,
 * content type, ETag/304 behavior, $id and the oneOf block types of
 * the combined schema.
 *
 * Deprecations are ignored because loading EXT:headless in a TYPO3 14.3
 * test instance may trigger core deprecation-108345 (see
 * ContentBlocksJsonResponseTest for details).
 *
 * @see https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/issues/22
 */
#[IgnoreDeprecations]
final class SchemaEndpointTest extends FunctionalTestCase
{
    private const SITE_IDENTIFIER = 'e2e-schema-endpoint';

    protected array $coreExtensionsToLoad = [
        'install',
    ];

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/nb_headless_content_blocks/Tests/Fixtures/Extensions/test_nb_headless_content_blocks',
        'typo3conf/ext/container',
        'typo3conf/ext/content_blocks',
        'typo3conf/ext/headless',
        'typo3conf/ext/nb_headless_content_blocks',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/e2e_page.csv');
        $this->writeSiteConfiguration();
    }

    #[Test]
    public function combinedSchemaIsDeliveredAsSchemaJsonResponse(): void
    {
        $response = $this->executeFrontendSubRequest($this->schemaEndpointRequest());

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/schema+json', $response->getHeaderLine('Content-Type'));
        self::assertNotSame('', $response->getHeaderLine('ETag'));

        $schema = $this->decodeJsonResponse($response);

        self::assertSame('http://json-schema.org/draft-07/schema#', $schema['$schema']);
        self::assertSame(
            'https://example.com/api/schema/content-blocks.schema.json',
            $schema['$id']
        );
    }

    #[Test]
    public function combinedSchemaContainsAllRegisteredContentBlocks(): void
    {
        $schema = $this->decodeJsonResponse(
            $this->executeFrontendSubRequest($this->schemaEndpointRequest())
        );

        $blockTypes = [];
        foreach ($schema['oneOf'] as $branch) {
            $blockTypes[] = $branch['properties']['type']['const'];
            self::assertSame(['$ref' => '#/definitions/ctype_' . $branch['properties']['type']['const']], $branch['properties']['data']);
        }

        self::assertContains('test_simple', $blockTypes);
        self::assertContains('test_filetest', $blockTypes);
        self::assertContains('test_richtext', $blockTypes);

        foreach (['linkObject', 'fileObject', 'categoryObject', 'errorObject'] as $sharedDefinition) {
            self::assertArrayHasKey($sharedDefinition, $schema['definitions']);
        }
    }

    #[Test]
    public function matchingEtagIsAnsweredWithNotModified(): void
    {
        $eTag = $this->executeFrontendSubRequest($this->schemaEndpointRequest())->getHeaderLine('ETag');

        $response = $this->executeFrontendSubRequest(
            $this->schemaEndpointRequest()->withHeader('If-None-Match', $eTag)
        );

        self::assertSame(304, $response->getStatusCode());
        self::assertSame('', (string)$response->getBody());
    }

    #[Test]
    public function otherRequestMethodsAreRejected(): void
    {
        $response = $this->executeFrontendSubRequest(
            $this->schemaEndpointRequest()->withMethod('POST')
        );

        self::assertSame(405, $response->getStatusCode());
        self::assertSame('GET, HEAD', $response->getHeaderLine('Allow'));
    }

    #[Test]
    public function requestsOutsideTheEndpointPathArePassedThrough(): void
    {
        $response = $this->executeFrontendSubRequest(
            $this->request('/some/other/path')
        );

        self::assertNotSame(200, $response->getStatusCode());
        self::assertStringNotContainsString('json-schema.org', (string)$response->getBody());
    }

    private function schemaEndpointRequest(): InternalRequest
    {
        return $this->request('/api/schema/content-blocks.schema.json');
    }

    private function request(string $path): InternalRequest
    {
        return (new InternalRequest('https://example.com' . $path))
            ->withServerParams([
                'SCRIPT_NAME' => '/index.php',
                'HTTP_HOST' => 'example.com',
                'SERVER_NAME' => 'example.com',
                'HTTPS' => 'on',
                'REMOTE_ADDR' => '127.0.0.1',
            ]);
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
settings:
  schemaEndpoint.enabled: true
  schemaEndpoint.idBase: 'https://example.com/api/schema'
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
