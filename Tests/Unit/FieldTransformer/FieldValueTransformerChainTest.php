<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\FieldTransformer;

use Netzbewegung\NbHeadlessContentBlocks\FieldTransformer\FieldValueTransformerChain;
use Netzbewegung\NbHeadlessContentBlocks\FieldTransformer\FieldValueTransformerInterface;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FieldValueTransformerChainTest extends UnitTestCase
{
    #[Test]
    public function supportsReturnsTrueWhenAnyTransformerSupportsTheField(): void
    {
        $field = $this->createField();
        $transformer = $this->createTransformer(supports: true);

        $subject = new FieldValueTransformerChain([$transformer]);

        self::assertTrue($subject->supports($field));
    }

    #[Test]
    public function supportsReturnsFalseWhenNoTransformerSupportsTheField(): void
    {
        $subject = new FieldValueTransformerChain([
            $this->createTransformer(supports: false),
        ]);

        self::assertFalse($subject->supports($this->createField()));
    }

    #[Test]
    public function transformReturnsValueUnchangedWhenNoTransformerMatches(): void
    {
        $subject = new FieldValueTransformerChain([
            $this->createTransformer(supports: false),
        ]);

        self::assertSame('untouched', $subject->transform('untouched', $this->createField(), $this->createContext()));
    }

    #[Test]
    public function transformAppliesFirstSupportingTransformer(): void
    {
        $field = $this->createField();
        $subject = new FieldValueTransformerChain([
            $this->createTransformer(supports: false),
            $this->createTransformer(supports: true, transformed: 'transformed'),
        ]);

        self::assertSame('transformed', $subject->transform('original', $field, $this->createContext()));
    }

    private function createContext(): Context
    {
        return new Context(null, null, [], $this->createMock(EventDispatcherInterface::class));
    }

    private function createField(): FieldTypeInterface&MockObject
    {
        return $this->createMock(FieldTypeInterface::class);
    }

    private function createTransformer(bool $supports, string $transformed = ''): FieldValueTransformerInterface&MockObject
    {
        $transformer = $this->createMock(FieldValueTransformerInterface::class);
        $transformer->method('supports')->willReturn($supports);
        if ($supports) {
            $transformer->method('transform')->willReturn($transformed);
        }

        return $transformer;
    }
}
