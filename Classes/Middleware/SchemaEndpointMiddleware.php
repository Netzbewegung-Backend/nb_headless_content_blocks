<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Middleware;

use Netzbewegung\NbHeadlessContentBlocks\Schema\JsonSchemaGenerator;
use Netzbewegung\NbHeadlessContentBlocks\Schema\SchemaEndpointAccess;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * Serves the combined Content Block JSON Schema over HTTP, so consumers
 * (frontend builds, IDEs, contract tests) can fetch it from a stable
 * URL instead of having the files copied around.
 *
 * The middleware runs after site resolution (per-site settings) and
 * before page resolution: matching requests are answered directly,
 * everything else is passed through to regular page rendering.
 *
 * Access is decided by SchemaEndpointAccess: available in Development
 * application contexts, or when the site setting "schemaEndpoint.enabled"
 * is turned on; a configured "schemaEndpoint.token" is always required.
 * Rejected requests answer with 404, indistinguishable from a disabled
 * endpoint, so the route does not leak whether it exists.
 */
final readonly class SchemaEndpointMiddleware implements MiddlewareInterface
{
    private const COMBINED_FILE_NAME = 'content-blocks.schema.json';

    private const DEFAULT_PATH = '/api/schema';

    public function __construct(
        private readonly JsonSchemaGenerator $jsonSchemaGenerator,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            return $handler->handle($request);
        }

        $path = rtrim('/' . trim((string)$this->setting($site, 'path', self::DEFAULT_PATH), '/'), '/');
        $path = $path === '' ? self::DEFAULT_PATH : $path;
        if ($request->getUri()->getPath() !== $path . '/' . self::COMBINED_FILE_NAME) {
            return $handler->handle($request);
        }

        $configuredToken = trim((string)$this->setting($site, 'token', ''));
        if (!SchemaEndpointAccess::isAllowed(
            Environment::getContext()->isDevelopment(),
            (bool)$this->setting($site, 'enabled', false),
            $configuredToken,
            trim($request->getHeaderLine('X-API-Token'))
        )) {
            return new JsonResponse(
                ['error' => 'Schema endpoint is disabled. It is available in Development application contexts or when the site setting "schemaEndpoint.enabled" is enabled.'],
                404
            );
        }

        if (!in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return new JsonResponse(
                ['error' => 'Method not allowed'],
                405,
                ['Allow' => 'GET, HEAD']
            );
        }

        $body = (string)json_encode(
            $this->jsonSchemaGenerator->generateCombined(trim((string)$this->setting($site, 'idBase', ''))),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        ) . LF;

        $cacheControl = $configuredToken === '' ? 'public, max-age=3600' : 'private, no-store';
        $eTag = '"' . md5($body) . '"';
        if (trim($request->getHeaderLine('If-None-Match')) === $eTag) {
            return new Response('php://temp', 304, [
                'ETag' => $eTag,
                'Cache-Control' => $cacheControl,
            ]);
        }

        $response = new Response(
            'php://temp',
            200,
            [
                'Content-Type' => 'application/schema+json',
                'ETag' => $eTag,
                'Cache-Control' => $cacheControl,
            ]
        );
        $response->getBody()->write($body);

        return $response;
    }

    private function setting(Site $site, string $key, mixed $default): mixed
    {
        return $site->getSettings()->get('schemaEndpoint.' . $key, $default);
    }
}
