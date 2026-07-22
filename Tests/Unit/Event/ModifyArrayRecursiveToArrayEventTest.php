<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\Event;

use PHPUnit\Framework\TestCase;
use Netzbewegung\NbHeadlessContentBlocks\Event\ModifyArrayRecursiveToArrayEvent;

final class ModifyArrayRecursiveToArrayEventTest extends TestCase
{
    public function testGetKeyReturnsKey(): void
    {
        $event = new ModifyArrayRecursiveToArrayEvent('testKey', 'testValue', null);

        $this->assertSame('testKey', $event->getKey());
    }

    public function testGetKeyReturnsIntegerKey(): void
    {
        $event = new ModifyArrayRecursiveToArrayEvent(123, 'testValue', null);

        $this->assertSame(123, $event->getKey());
    }

    public function testGetValueReturnsValue(): void
    {
        $event = new ModifyArrayRecursiveToArrayEvent('key', 'myValue', null);

        $this->assertSame('myValue', $event->getValue());
    }

    public function testGetValueReturnsNull(): void
    {
        $event = new ModifyArrayRecursiveToArrayEvent('key', null, null);

        $this->assertNull($event->getValue());
    }

    public function testGetTcaFieldDefinitionReturnsNull(): void
    {
        $event = new ModifyArrayRecursiveToArrayEvent('key', 'value', null);

        $this->assertNull($event->getTcaFieldDefinition());
    }

    public function testIsHandledReturnsFalseByDefault(): void
    {
        $event = new ModifyArrayRecursiveToArrayEvent('key', 'value', null);

        $this->assertFalse($event->isHandled());
    }

    public function testSetProcessedValueMarksEventAsHandled(): void
    {
        $event = new ModifyArrayRecursiveToArrayEvent('key', 'value', null);

        $event->setProcessedValue('processedValue');

        $this->assertTrue($event->isHandled());
    }

    public function testGetProcessedValueReturnsSetValue(): void
    {
        $event = new ModifyArrayRecursiveToArrayEvent('key', 'value', null);

        $event->setProcessedValue('processedValue');

        $this->assertSame('processedValue', $event->getProcessedValue());
    }

    public function testGetProcessedValueReturnsNullByDefault(): void
    {
        $event = new ModifyArrayRecursiveToArrayEvent('key', 'value', null);

        $this->assertNull($event->getProcessedValue());
    }

    public function testSetProcessedValueCanSetNull(): void
    {
        $event = new ModifyArrayRecursiveToArrayEvent('key', 'value', null);

        $event->setProcessedValue(null);

        $this->assertTrue($event->isHandled());
        $this->assertNull($event->getProcessedValue());
    }
}
