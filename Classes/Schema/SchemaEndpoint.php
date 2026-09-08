<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Schema;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Attribute\AsAllowedCallable;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Http\JsonResponse;

/**
 * TypoScript USER function of the JSON Schema endpoint page type
 * (see Configuration/Sets/HeadlessContentBlock/setup.typoscript).
 *
 * Delivers the combined Content Block schema. Access is allowed in
 * Development application contexts, or anywhere else only when the
 * site setting "schemaEndpoint.enabled" is turned on — otherwise the
 * endpoint answers with 404 to avoid leaking the content model of a
 * production site. A configured site setting "schemaEndpoint.token"
 * additionally requires authentication on every request (X-API-Token
 * header — a query parameter is not supported, because the frontend
 * cHash mechanism strips unknown GET parameters from page-type URLs);
 * a missing or wrong token also answers with 404, indistinguishable
 * from a disabled endpoint.
 */
final class SchemaEndpoint
{
    /**
     * Page type of the schema endpoint, request via ?type=<PAGE_TYPE>.
     */
    public const PAGE_TYPE = 1788873600;

    public function __construct(
        private readonly JsonSchemaGenerator $jsonSchemaGenerator,
    ) {}

    /**
     * @param array<string, mixed> $conf TypoScript conf of the USER object:
     *        "enabled", "idBase" and "token", fed from the site settings
     *        schemaEndpoint.enabled / schemaEndpoint.idBase / schemaEndpoint.token
     */
    #[AsAllowedCallable]
    public function deliverSchema(string $content, array $conf, ?ServerRequestInterface $request = null): string
    {
        $enabled = in_array(strtolower(trim((string)($conf['enabled'] ?? ''))), ['1', 'true', 'yes'], true);
        if (!SchemaEndpointAccess::isAllowed(
            Environment::getContext()->isDevelopment(),
            $enabled,
            trim((string)($conf['token'] ?? '')),
            $this->providedToken($request)
        )) {
            throw new ImmediateResponseException(
                new JsonResponse(
                    ['error' => 'Schema endpoint is disabled. It is available in Development application contexts or when the site setting "schemaEndpoint.enabled" is enabled.'],
                    404
                ),
                1788873601
            );
        }

        $schema = $this->jsonSchemaGenerator->generateCombined(
            trim((string)($conf['idBase'] ?? ''))
        );

        return (string)json_encode(
            $schema,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }

    private function providedToken(?ServerRequestInterface $request): string
    {
        if ($request === null) {
            return '';
        }
        return trim($request->getHeaderLine('X-API-Token'));
    }
}
