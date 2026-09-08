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
 * with the site setting "schemaEndpoint.enabled" left at its default
 * (false). Functional tests run in the "Testing" application context,
 * which is not Development — the endpoint must therefore answer with
 * 404, so the content model of a site is never leaked by default.
 *
 * Deprecations are ignored because loading EXT:headless in a TYPO3 14.3
 * test instance may trigger core deprecation-108345 (see
 * ContentBlocksJsonResponseTest for details).
 *
 * @see https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/issues/22
 */
#[IgnoreDeprecations]
final class SchemaEndpointDisabledTest extends FunctionalTestCase
{
    private const SITE_IDENTIFIER = 'e2e-schema-endpoint-disabled';

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
    public function disabledEndpointAnswersWith404(): void
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('https://example.com/'))
                ->withPageId(1)
                ->withQueryParameter('type', '1788873600')
        );

        self::assertSame(404, $response->getStatusCode());
        self::assertSame(
            ['error' => 'Schema endpoint is disabled. It is available in Development application contexts or when the site setting "schemaEndpoint.enabled" is enabled.'],
            $this->decodeJsonResponse($response)
        );
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
