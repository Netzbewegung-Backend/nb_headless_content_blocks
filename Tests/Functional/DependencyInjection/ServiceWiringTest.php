<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Functional\DependencyInjection;

use Netzbewegung\NbHeadlessContentBlocks\ContentBlocks\HeadlessYamlLoader;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\UnknownTypeNormalizer;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Guards the DI wiring that autowiring cannot express: the logger tag for
 * the UnknownTypeNormalizer (autoconfigure is disabled) and the explicit
 * cache.runtime wiring of the HeadlessYamlLoader. Without these, both
 * would silently degrade (no log / no cache, YAML re-parsed per element).
 */
final class ServiceWiringTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/nb_headless_content_blocks/Tests/Fixtures/Extensions/test_nb_headless_content_blocks',
        'typo3conf/ext/container',
        'typo3conf/ext/content_blocks',
        'typo3conf/ext/nb_headless_content_blocks',
    ];

    #[Test]
    public function loggerIsInjectedIntoUnknownTypeNormalizer(): void
    {
        $normalizer = $this->get(UnknownTypeNormalizer::class);

        $logger = $this->readPrivateProperty($normalizer, 'logger');

        self::assertInstanceOf(LoggerInterface::class, $logger);
    }

    #[Test]
    public function runtimeCacheIsInjectedIntoHeadlessYamlLoader(): void
    {
        $loader = $this->get(HeadlessYamlLoader::class);

        $cache = $this->readPrivateProperty($loader, 'cache');

        self::assertInstanceOf(FrontendInterface::class, $cache);
    }

    private function readPrivateProperty(object $object, string $propertyName): mixed
    {
        $property = new \ReflectionProperty($object, $propertyName);

        return $property->getValue($object);
    }
}
