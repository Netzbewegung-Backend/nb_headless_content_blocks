<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\DataProcessing\ToArray;

use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray\TypolinkParameterToArray;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\LinkHandling\TypoLinkCodecService;
use TYPO3\CMS\Core\LinkHandling\TypolinkParameter;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;

final class TypolinkParameterToArrayTest extends TestCase
{
    public function testEmptyUrlReturnsNull(): void
    {
        $typolinkParameter = new TypolinkParameter(url: '');

        $subject = new TypolinkParameterToArray($typolinkParameter);

        self::assertNull($subject->toArray());
    }

    public function testZeroUrlReturnsNull(): void
    {
        $typolinkParameter = new TypolinkParameter(url: '0');

        $subject = new TypolinkParameterToArray($typolinkParameter);

        self::assertNull($subject->toArray());
    }

    public function testToArrayReturnsErrorMessageOnUnableToLinkException(): void
    {
        $typolinkParameter = new TypolinkParameter(url: 'https://example.com');

        $linkFactory = $this->createMock(LinkFactory::class);
        $linkFactory->method('createUri')->willThrowException(
            new UnableToLinkException('Link could not be created', 1644321596)
        );

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(
            static fn(object $event) => $event
        );
        $typoLinkCodecService = new TypoLinkCodecService($eventDispatcher);

        $subject = new class ($typolinkParameter, $linkFactory, $typoLinkCodecService) extends TypolinkParameterToArray {
            public function __construct(
                TypolinkParameter $typolinkParameter,
                private LinkFactory $mockLinkFactory,
                private TypoLinkCodecService $mockCodecService,
            ) {
                parent::__construct($typolinkParameter);
            }

            protected function getLinkFactory(): LinkFactory
            {
                return $this->mockLinkFactory;
            }

            protected function getTypoLinkCodecService(): TypoLinkCodecService
            {
                return $this->mockCodecService;
            }
        };

        $result = $subject->toArray();

        self::assertSame('', $result['url']);
        self::assertSame('', $result['target']);
        self::assertSame('', $result['type']);
        self::assertSame('', $result['title']);
        self::assertSame([], $result['config']);
        self::assertSame([], $result['attr']);
        self::assertSame('Link could not be created', $result['__errorMessage']);
    }
}
