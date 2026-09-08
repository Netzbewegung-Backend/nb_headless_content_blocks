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
 * End-to-end tests of the JSON Schema endpoint (page type 1788873600)
 * with the site setting "schemaEndpoint.token" configured: the token is
 * then required on every request via the X-API-Token header (functional
 * tests run in the "Testing" application context, so even the enabled
 * setting alone must not be enough). A missing or wrong token answers
 * with 404, indistinguishable from a disabled endpoint.
 *
 * Deprecations are ignored because loading EXT:headless in a TYPO3 14.3
 * test instance may trigger core deprecation-108345 (see
 * ContentBlocksJsonResponseTest for details).
 *
 * @see https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/issues/22
 */
#[IgnoreDeprecations]
final class SchemaEndpointTokenTest extends FunctionalTestCase
{
    private const SITE_IDENTIFIER = 'e2e-schema-endpoint-token';

    private const TOKEN = 'e2e-test-token';

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
    public function requestWithoutTokenIsRejected(): void
    {
        $response = $this->executeFrontendSubRequest($this->schemaEndpointRequest());

        self::assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function requestWithWrongTokenIsRejected(): void
    {
        $response = $this->executeFrontendSubRequest(
            $this->schemaEndpointRequest()->withHeader('X-API-Token', 'wrong-token')
        );

        self::assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function requestWithCorrectTokenHeaderIsServed(): void
    {
        $response = $this->executeFrontendSubRequest(
            $this->schemaEndpointRequest()->withHeader('X-API-Token', self::TOKEN)
        );

        self::assertSame(200, $response->getStatusCode());
        $this->assertCombinedSchema($response);
    }

    private function schemaEndpointRequest(): InternalRequest
    {
        return (new InternalRequest('https://example.com/'))
            ->withPageId(1)
            ->withQueryParameter('type', '1788873600');
    }

    private function assertCombinedSchema(ResponseInterface $response): void
    {
        $schema = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($schema);
        self::assertSame('http://json-schema.org/draft-07/schema#', $schema['$schema']);
        self::assertNotEmpty($schema['oneOf']);
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
  schemaEndpoint.token: 'e2e-test-token'
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
