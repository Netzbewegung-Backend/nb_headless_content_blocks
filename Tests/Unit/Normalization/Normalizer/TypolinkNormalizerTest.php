<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\Normalization\Normalizer;

use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer\TypolinkNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\LinkHandling\TypoLinkCodecService;
use TYPO3\CMS\Core\LinkHandling\TypolinkParameter;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\Frontend\Typolink\LinkResult;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TypolinkNormalizerTest extends UnitTestCase
{
    public function testEmptyUrlIsNormalizedToNull(): void
    {
        $subject = new TypolinkNormalizer($this->createLinkFactory(), $this->createTypoLinkCodecService());

        self::assertNull($subject->normalize(new TypolinkParameter(''), $this->createContext()));
        self::assertNull($subject->normalize(new TypolinkParameter('0'), $this->createContext()));
    }

    public function testLinkResultShapeIsFrozenContract(): void
    {
        $linkResult = $this->createConfiguredMock(LinkResult::class, [
            'getUrl' => 'https://example.com',
            'getTarget' => '_blank',
            'getType' => 'url',
            'getLinkText' => 'https://example.com',
            'getLinkConfiguration' => ['parameter' => 'https://example.com'],
            'getAttributes' => ['href' => 'https://example.com'],
        ]);

        $linkFactory = $this->createLinkFactory();
        $linkFactory->method('createUri')->willReturn($linkResult);

        $subject = new TypolinkNormalizer($linkFactory, $this->createTypoLinkCodecService());

        $result = $subject->normalize(new TypolinkParameter('https://example.com'), $this->createContext());

        self::assertSame('https://example.com', $result['url']);
        self::assertSame('_blank', $result['target']);
        self::assertSame('url', $result['type']);
        self::assertSame('https://example.com', $result['title']);
        self::assertSame(['parameter' => 'https://example.com'], $result['config']);
        self::assertSame(['href' => 'https://example.com'], $result['attr']);
    }

    public function testUnresolvableLinkReturnsErrorShape(): void
    {
        $linkFactory = $this->createLinkFactory();
        $linkFactory->method('createUri')->willThrowException(
            new UnableToLinkException('Could not link', 123, null, 't3://page?uid=999')
        );

        $subject = new TypolinkNormalizer($linkFactory, $this->createTypoLinkCodecService());

        $result = $subject->normalize(new TypolinkParameter('t3://page?uid=999'), $this->createContext());

        self::assertSame('', $result['url']);
        self::assertSame('', $result['target']);
        self::assertSame('', $result['type']);
        self::assertSame('', $result['title']);
        self::assertSame([], $result['config']);
        self::assertSame([], $result['attr']);
        self::assertSame('Could not link', $result['__errorMessage']);
    }

    private function createLinkFactory(): LinkFactory&MockObject
    {
        return $this->createMock(LinkFactory::class);
    }

    private function createTypoLinkCodecService(): TypoLinkCodecService
    {
        return new TypoLinkCodecService(new EventDispatcher(
            new class implements ListenerProviderInterface {
                public function getListenersForEvent(object $event): iterable
                {
                    return [];
                }
            }
        ));
    }

    private function createContext(): Context
    {
        return new Context(null, null, [], $this->createMock(EventDispatcherInterface::class));
    }
}
