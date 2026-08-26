<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\Normalization;

use Netzbewegung\NbHeadlessContentBlocks\ContentBlocks\ContentBlocksIdentifierMapper;
use Netzbewegung\NbHeadlessContentBlocks\ContentBlocks\HeadlessYamlLoader;
use Netzbewegung\NbHeadlessContentBlocks\Event\ModifyArrayRecursiveToArrayEvent;
use Netzbewegung\NbHeadlessContentBlocks\FieldTransformer\FieldValueTransformerChain;
use Netzbewegung\NbHeadlessContentBlocks\FieldTransformer\String\PasswordBlanker;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer\DateTimeNormalizer;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer\RecordNormalizer;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer\ScalarNormalizer;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\NormalizerChain;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\RecordArrayBuilder;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\UnknownTypeNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\ListenerProviderInterface;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\ContentBlocks\Registry\AutomaticLanguageKeysRegistry;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RecordArrayBuilderTest extends UnitTestCase
{
    public function testScalarValuesArePassedThrough(): void
    {
        $subject = $this->createBuilder();

        $result = $subject->build($this->createRecord(['nullValue' => null, 'intValue' => 42, 'stringValue' => 'text']));

        self::assertSame(['intValue' => 42, 'nullValue' => null, 'stringValue' => 'text'], $result);
    }

    public function testBooleanAndFloatValuesAreNormalizedToNull(): void
    {
        $subject = $this->createBuilder();

        $result = $subject->build($this->createRecord(['boolValue' => true, 'floatValue' => 13.37]));

        self::assertSame(['boolValue' => null, 'floatValue' => null], $result);
    }

    public function testDateTimeIsFormattedAsW3C(): void
    {
        $dateTime = new \DateTimeImmutable('2026-07-22 10:15:30', new \DateTimeZone('UTC'));
        $subject = $this->createBuilder();

        $result = $subject->build($this->createRecord(['createdAt' => $dateTime]));

        self::assertSame(['createdAt' => $dateTime->format(\DateTimeImmutable::W3C)], $result);
    }

    public function testSystemFieldsAreStripped(): void
    {
        $subject = $this->createBuilder();

        $result = $subject->build($this->createRecord([
            'uid' => 1,
            'pid' => 0,
            'colPos' => 0,
            'CType' => 'test_element',
            'foreign_table_parent_uid' => 0,
            'tx_container_parent' => 0,
            'bodytext' => 'Hello World',
        ]));

        self::assertSame(['bodytext' => 'Hello World'], $result);
    }

    public function testResultIsSortedByKey(): void
    {
        $subject = $this->createBuilder();

        $result = $subject->build($this->createRecord(['zulu' => 1, 'alpha' => 2, 'mike' => 3]));

        self::assertSame(['alpha' => 2, 'mike' => 3, 'zulu' => 1], $result);
    }

    public function testNestedArraysAreNormalizedRecursively(): void
    {
        $subject = $this->createBuilder();

        $result = $subject->build($this->createRecord(['level1' => ['level2' => ['key' => 'value']]]));

        self::assertSame(['level1' => ['level2' => ['key' => 'value']]], $result);
    }

    public function testHandledEventProcessedValueIsUsed(): void
    {
        $listener = static function (ModifyArrayRecursiveToArrayEvent $event): void {
            $event->setProcessedValue('processed');
        };
        $subject = $this->createBuilder(listeners: [$listener]);

        $result = $subject->build($this->createRecord(['key' => 'original']));

        self::assertSame(['key' => 'processed'], $result);
    }

    public function testEventReceivesKeyAndValue(): void
    {
        $receivedEvents = [];
        $listener = static function (ModifyArrayRecursiveToArrayEvent $event) use (&$receivedEvents): void {
            $receivedEvents[] = [$event->getKey(), $event->getValue()];
        };
        $subject = $this->createBuilder(listeners: [$listener]);

        $subject->build($this->createRecord(['myKey' => 'myValue']));

        self::assertSame([['myKey', 'myValue']], $receivedEvents);
    }

    public function testPasswordValueIsBlankedViaTransformer(): void
    {
        $schema = new TcaSchema('tt_content', new \TYPO3\CMS\Core\Schema\Field\FieldCollection([
            'secret' => new \TYPO3\CMS\Core\Schema\Field\PasswordFieldType('secret', ['type' => 'password']),
        ]), []);
        $subject = $this->createBuilder(tcaSchema: $schema);

        $result = $subject->build($this->createRecord(['secret' => 'mySecretValue']));

        self::assertSame(['secret' => ''], $result);
    }

    public function testStringFieldWithoutSchemaIsPassedThrough(): void
    {
        $subject = $this->createBuilder();

        $result = $subject->build($this->createRecord(['unknown_field' => 'hello']));

        self::assertSame(['unknown_field' => 'hello'], $result);
    }

    public function testFileDoesNotExistExceptionResultsInErrorMessage(): void
    {
        $record = $this->createMock(Record::class);
        $record->method('getMainType')->willReturn('tt_content');
        $record->method('getRecordType')->willReturn(null);
        $record->method('toArray')->willThrowException(
            new \TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException('File not found')
        );

        $subject = $this->createBuilder();

        self::assertSame(['__errorMessage' => 'File not found'], $subject->build($record));
    }

    /**
     * @param array<string, mixed> $row
     */
    private function createRecord(array $row): Record&MockObject
    {
        $record = $this->createMock(Record::class);
        $record->method('getMainType')->willReturn('tt_content');
        $record->method('getRecordType')->willReturn(null);
        $record->method('toArray')->willReturn($row);

        return $record;
    }

    /**
     * @param callable[] $listeners
     */
    private function createBuilder(array $listeners = [], ?TcaSchema $tcaSchema = null): RecordArrayBuilder
    {
        $listenerProvider = new class ($listeners) implements ListenerProviderInterface {
            public function __construct(private readonly array $listeners) {}

            public function getListenersForEvent(object $event): iterable
            {
                return $this->listeners;
            }
        };
        $eventDispatcher = new EventDispatcher($listenerProvider);

        $chain = new NormalizerChain(
            [
                new ScalarNormalizer(),
                new DateTimeNormalizer(),
                new RecordNormalizer(),
            ],
            new UnknownTypeNormalizer()
        );

        $tcaSchemaFactory = $this->createMock(TcaSchemaFactory::class);
        if ($tcaSchema !== null) {
            $tcaSchemaFactory->method('has')->with('tt_content')->willReturn(true);
            $tcaSchemaFactory->method('get')->with('tt_content')->willReturn($tcaSchema);
        } else {
            $tcaSchemaFactory->method('has')->willReturn(false);
        }

        $tableDefinitionCollection = new TableDefinitionCollection(new AutomaticLanguageKeysRegistry());

        return new RecordArrayBuilder(
            $chain,
            new FieldValueTransformerChain([new PasswordBlanker()]),
            new ContentBlocksIdentifierMapper($tableDefinitionCollection),
            $tableDefinitionCollection,
            $tcaSchemaFactory,
            new HeadlessYamlLoader(null),
            $eventDispatcher
        );
    }
}
