<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Schema;

/**
 * Decides whether the JSON Schema endpoint may serve a request.
 *
 * - A configured token is always required, in any application context
 *   (this also protects non-production environments that set one).
 * - Without a token the endpoint is available in Development application
 *   contexts, or everywhere when the site setting "schemaEndpoint.enabled"
 *   is turned on.
 */
final class SchemaEndpointAccess
{
    public static function isAllowed(bool $isDevelopment, bool $enabled, string $configuredToken, string $providedToken): bool
    {
        if ($configuredToken !== '') {
            return hash_equals($configuredToken, $providedToken);
        }
        return $isDevelopment || $enabled;
    }
}
