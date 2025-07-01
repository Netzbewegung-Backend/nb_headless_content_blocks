<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\ToArray;

use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray\TypolinkParameterToArray;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\LinkHandling\TypoLinkCodecService;
use TYPO3\CMS\Core\LinkHandling\TypolinkParameter;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Frontend\Typolink\LinkResult;

class TypolinkParameterToArrayTest extends UnitTestCase
{
    #[Test]
    public function returnsNullForEmptyUrl(): void
    {
        $typolinkParameter = new TypolinkParameter('');
        $subject = new TypolinkParameterToArray(
            $typolinkParameter, 
            new TypoLinkCodecService(new NoopEventDispatcher()), 
            $this->createMock(LinkFactory::class)
        );
        self::assertNull($subject->toArray());
    }

    #[Test]
    public function returnsNullForZeroUrl(): void
    {
        $typolinkParameter = new TypolinkParameter('0');
        $subject = new TypolinkParameterToArray(
            $typolinkParameter, 
            new TypoLinkCodecService(new NoopEventDispatcher()), 
            $this->createMock(LinkFactory::class)
        );
        self::assertNull($subject->toArray());
    }

    #[Test]
    public function returnsCompleteLinkDataOnSuccess(): void
    {
        $url = 'https://example.com';
        $target = '_blank';
        $type = 'url';
        $title = 'Example';
        $config = ['parameter' => '123'];
        $attr = ['class' => 'link', 'href' => $url, 'target' => $target];

        $typolinkParameter = new TypolinkParameter(
            $url,
            $target,
            'link',
            $title,
            '',
            $config,
        );

        $linkResult = (new LinkResult(LinkService::TYPE_URL, $url))
            ->withTarget($target)
            ->withLinkText($title)
            ->withLinkConfiguration($config)
            ->withAttributes($attr);
        $linkFactory = $this->getMockBuilder(LinkFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createUri'])
            ->getMock();
        $linkFactory->method('createUri')->willReturn($linkResult);

        $subject = new TypolinkParameterToArray(
            $typolinkParameter, 
            new TypoLinkCodecService(new NoopEventDispatcher()), 
            $linkFactory
        );

        $result = $subject->toArray();

        self::assertIsArray($result);
        self::assertEquals($url, $result['url']);
        self::assertEquals($target, $result['target']);
        self::assertEquals($type, $result['type']);
        self::assertEquals($title, $result['title']);
        self::assertEquals($config, $result['config']);
        self::assertEquals($attr, $result['attr']);
    }

    #[Test]
    public function returnsErrorMessageOnLinkException(): void {}
}
