<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\Normalization;

use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\NormalizerChain;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\UnknownTypeNormalizer;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ContextTest extends UnitTestCase
{
    #[Test]
    public function exposesConstructorState(): void
    {
        $schema = self::createStub(TcaSchema::class);
        $request = self::createStub(ServerRequestInterface::class);
        $eventDispatcher = self::createStub(EventDispatcherInterface::class);

        $subject = new Context($schema, $request, ['dateTimeFormat' => 'Y'], $eventDispatcher, [], null);

        self::assertSame($schema, $subject->getTcaSchema());
        self::assertSame($request, $subject->getRequest());
        self::assertSame(['dateTimeFormat' => 'Y'], $subject->getOptions());
        self::assertSame('Y', $subject->getOption('dateTimeFormat'));
        self::assertNull($subject->getOption('unknown'));
        self::assertSame($eventDispatcher, $subject->getEventDispatcher());
        self::assertNull($subject->getContentObjectRenderer());
    }

    #[Test]
    public function getChainThrowsWithoutChain(): void
    {
        $subject = new Context(null, null, [], self::createStub(EventDispatcherInterface::class));

        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1782745501);

        $subject->getChain();
    }

    #[Test]
    public function buildRecordThrowsWithoutRecordBuilder(): void
    {
        $subject = new Context(null, null, [], self::createStub(EventDispatcherInterface::class));

        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1782745502);

        $subject->buildRecord(self::createStub(RecordInterface::class));
    }

    #[Test]
    public function withTcaSchemaKeepsChainAndRecordBuilder(): void
    {
        $chain = new NormalizerChain([], new UnknownTypeNormalizer());
        $subject = new Context(null, null, [], self::createStub(EventDispatcherInterface::class));
        $subject->setChain($chain);
        $subject->setRecordBuilder(static fn(): array => ['result' => 'built']);

        $clone = $subject->withTcaSchema(self::createStub(TcaSchema::class));

        self::assertSame($chain, $clone->getChain());
        self::assertSame(['result' => 'built'], $clone->buildRecord(self::createStub(RecordInterface::class)));
    }

    #[Test]
    public function withCurrentFieldIdentifierExposesFileProcessing(): void
    {
        $chain = new NormalizerChain([], new UnknownTypeNormalizer());
        $subject = new Context(null, null, [], self::createStub(EventDispatcherInterface::class), [
            'my_image' => ['mobile' => 'width=200c'],
        ]);
        $subject->setChain($chain);
        $subject->setRecordBuilder(static fn(): array => []);

        self::assertSame([], $subject->getFileProcessingForCurrentField());

        $clone = $subject->withCurrentFieldIdentifier('my_image');

        self::assertSame(['mobile' => 'width=200c'], $clone->getFileProcessingForCurrentField());
        // the original context is not modified
        self::assertSame([], $subject->getFileProcessingForCurrentField());
    }
}
