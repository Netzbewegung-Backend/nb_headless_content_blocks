<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Schema;

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
 * production site.
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
     *        "enabled" and "idBase", fed from the site settings
     *        schemaEndpoint.enabled / schemaEndpoint.idBase
     */
    #[AsAllowedCallable]
    public function deliverSchema(string $content, array $conf): string
    {
        if (!self::accessIsAllowed($conf)) {
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

    /**
     * @param array<string, mixed> $conf
     */
    private function accessIsAllowed(array $conf): bool
    {
        if (Environment::getContext()->isDevelopment()) {
            return true;
        }
        // Site settings may arrive as "1"/"true" (or an empty string when
        // the boolean setting defaults to false), never as PHP bool.
        return in_array(strtolower(trim((string)($conf['enabled'] ?? ''))), ['1', 'true', 'yes'], true);
    }
}
