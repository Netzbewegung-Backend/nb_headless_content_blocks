<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\FieldTransformer\String;

use Netzbewegung\NbHeadlessContentBlocks\FieldTransformer\String\RichtextParser;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RichtextParserTest extends UnitTestCase
{
    #[Test]
    public function supportsOnlyAcceptsTextFieldTypes(): void
    {
        // TextFieldType is final and needs full schema information; the
        // negative case is covered here, the positive case functionally.
        $subject = new RichtextParser();

        self::assertFalse($subject->supports($this->createField()));
    }

    #[Test]
    public function transformReturnsValueUnchangedWithoutContentObjectRenderer(): void
    {
        $subject = new RichtextParser();

        $result = $subject->transform(
            '<p>raw</p>',
            $this->createField(),
            new Context(null, null, [], $this->createMock(EventDispatcherInterface::class))
        );

        self::assertSame('<p>raw</p>', $result);
    }

    private function createField(): FieldTypeInterface&MockObject
    {
        return $this->createMock(FieldTypeInterface::class);
    }
}
