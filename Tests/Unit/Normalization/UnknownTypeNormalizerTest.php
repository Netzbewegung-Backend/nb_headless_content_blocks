<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\Normalization;

use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\UnknownTypeNormalizer;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class UnknownTypeNormalizerTest extends UnitTestCase
{
    #[Test]
    public function supportsEverything(): void
    {
        self::assertTrue((new UnknownTypeNormalizer())->supports(new \stdClass(), $this->createContext()));
    }

    #[Test]
    public function normalizeReturnsNullForUnsupportedValue(): void
    {
        $subject = new UnknownTypeNormalizer();

        self::assertNull($subject->normalize(new \stdClass(), $this->createContext()));
    }

    #[Test]
    public function normalizeLogsDroppedType(): void
    {
        // psr/log is replaced by typo3/cms-core, so no TestLogger is available
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('debug')->with(
            self::identicalTo('Value of type {type} could not be normalized to a JSON representation and was replaced with null.'),
            self::callback(static fn(array $context): bool => ($context['type'] ?? '') === 'stdClass')
        );

        $subject = new UnknownTypeNormalizer();
        $subject->setLogger($logger);

        $subject->normalize(new \stdClass(), $this->createContext());
    }

    private function createContext(): Context
    {
        return new Context(null, null, [], $this->createMock(EventDispatcherInterface::class));
    }
}
