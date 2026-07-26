<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\DataProcessing\ToArray;

use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray\ArrayRecursiveToArray;
use Netzbewegung\NbHeadlessContentBlocks\Event\ModifyArrayRecursiveToArrayEvent;
use Psr\EventDispatcher\ListenerProviderInterface;
use TYPO3\CMS\ContentBlocks\Definition\Capability\TableDefinitionCapability;
use TYPO3\CMS\ContentBlocks\Definition\ContentType\ContentType;
use TYPO3\CMS\ContentBlocks\Definition\ContentType\ContentTypeDefinitionCollection;
use TYPO3\CMS\ContentBlocks\Definition\PaletteDefinitionCollection;
use TYPO3\CMS\ContentBlocks\Definition\SqlColumnDefinitionCollection;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinition;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\ContentBlocks\Definition\TcaFieldDefinition;
use TYPO3\CMS\ContentBlocks\Definition\TcaFieldDefinitionCollection;
use TYPO3\CMS\ContentBlocks\FieldType\PasswordFieldType;
use TYPO3\CMS\ContentBlocks\Registry\AutomaticLanguageKeysRegistry;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ArrayRecursiveToArrayTest extends UnitTestCase
{
    public function testNullValueIsPassedThrough(): void
    {
        $subject = $this->createSubject(['key' => null]);

        self::assertSame(['key' => null], $subject->toArray());
    }

    public function testIntegerValueIsPassedThrough(): void
    {
        $subject = $this->createSubject(['key' => 42]);

        self::assertSame(['key' => 42], $subject->toArray());
    }

    public function testStringValueIsPassedThrough(): void
    {
        $subject = $this->createSubject(['key' => 'value']);

        self::assertSame(['key' => 'value'], $subject->toArray());
    }

    public function testBooleanValueIsDropped(): void
    {
        $subject = $this->createSubject(['key' => true]);

        self::assertSame([], $subject->toArray());
    }

    public function testFloatValueIsDropped(): void
    {
        $subject = $this->createSubject(['key' => 13.37]);

        self::assertSame([], $subject->toArray());
    }

    public function testDateTimeImmutableIsFormattedAsW3C(): void
    {
        $dateTime = new \DateTimeImmutable('2026-07-22 10:15:30', new \DateTimeZone('UTC'));

        $subject = $this->createSubject(['key' => $dateTime]);

        self::assertSame(['key' => $dateTime->format(\DateTimeImmutable::W3C)], $subject->toArray());
    }

    public function testNestedArrayIsProcessedRecursively(): void
    {
        $subject = $this->createSubject([
            'level1' => [
                'level2' => [
                    'key' => 'value',
                ],
            ],
        ]);

        self::assertSame([
            'level1' => [
                'level2' => [
                    'key' => 'value',
                ],
            ],
        ], $subject->toArray());
    }

    public function testResultIsSortedByKey(): void
    {
        $subject = $this->createSubject([
            'zulu' => 1,
            'alpha' => 2,
            'mike' => 3,
        ]);

        self::assertSame([
            'alpha' => 2,
            'mike' => 3,
            'zulu' => 1,
        ], $subject->toArray());
    }

    public function testHandledEventProcessedValueIsUsed(): void
    {
        $listener = static function (ModifyArrayRecursiveToArrayEvent $event): void {
            $event->setProcessedValue('processed');
        };

        $subject = $this->createSubject(['key' => 'original'], [$listener]);

        self::assertSame(['key' => 'processed'], $subject->toArray());
    }

    public function testEventReceivesKeyAndValue(): void
    {
        $receivedEvents = [];
        $listener = static function (ModifyArrayRecursiveToArrayEvent $event) use (&$receivedEvents): void {
            $receivedEvents[] = [$event->getKey(), $event->getValue()];
        };

        $subject = $this->createSubject(['myKey' => 'myValue'], [$listener]);
        $subject->toArray();

        self::assertSame([['myKey', 'myValue']], $receivedEvents);
    }

    public function testPasswordFieldIsEmptied(): void
    {
        $tableDefinition = $this->createTableDefinitionWithPasswordField('secret_password');
        $subject = $this->createSubjectWithTableDefinition(
            ['secret_password' => 'mySecretValue'],
            $tableDefinition
        );

        self::assertSame(['secret_password' => ''], $subject->toArray());
    }

    public function testStringWithNoTableDefinitionIsPassedThrough(): void
    {
        $subject = $this->createSubject(['key' => 'hello']);

        self::assertSame(['key' => 'hello'], $subject->toArray());
    }

    public function testStringWithIntKeyIsPassedThrough(): void
    {
        $tableDefinition = $this->createTableDefinitionWithPasswordField('myField');
        $subject = $this->createSubjectWithTableDefinition(
            ['keep' => 'this string'],
            $tableDefinition
        );

        self::assertSame(['keep' => 'this string'], $subject->toArray());
    }

    /**
     * @param callable[] $listeners
     */
    private function createSubject(array $array, array $listeners = []): ArrayRecursiveToArray
    {
        $tableDefinitionCollection = new TableDefinitionCollection(new AutomaticLanguageKeysRegistry());

        return new ArrayRecursiveToArray(
            $array,
            null,
            $tableDefinitionCollection,
            $this->createEventDispatcher($listeners)
        );
    }

    /**
     * @param callable[] $listeners
     */
    private function createEventDispatcher(array $listeners): EventDispatcher
    {
        $listenerProvider = new class ($listeners) implements ListenerProviderInterface {
            public function __construct(private readonly array $listeners) {}

            public function getListenersForEvent(object $event): iterable
            {
                return $this->listeners;
            }
        };

        return new EventDispatcher($listenerProvider);
    }

    /**
     * @param callable[] $listeners
     */
    private function createSubjectWithTableDefinition(array $array, TableDefinition $tableDefinition, array $listeners = []): ArrayRecursiveToArray
    {
        $tableDefinitionCollection = new TableDefinitionCollection(new AutomaticLanguageKeysRegistry());

        return new ArrayRecursiveToArray(
            $array,
            $tableDefinition,
            $tableDefinitionCollection,
            $this->createEventDispatcher($listeners)
        );
    }

    private function createTableDefinitionWithPasswordField(string $fieldIdentifier): TableDefinition
    {
        $passwordFieldType = new PasswordFieldType();

        $args = [
            'parentContentType' => ContentType::CONTENT_ELEMENT,
            'identifier' => $fieldIdentifier,
            'uniqueIdentifier' => $fieldIdentifier,
            'labelPath' => '',
            'descriptionPath' => '',
            'placeholderPath' => '',
            'useExistingField' => false,
            'fieldType' => $passwordFieldType,
        ];
        if (property_exists(TcaFieldDefinition::class, 'parentTable')) {
            $args['parentTable'] = 'tt_content';
        }
        $tcaFieldDefinition = new TcaFieldDefinition(...$args);

        $tcaFieldDefinitionCollection = TcaFieldDefinitionCollection::createFromArray([], 'tt_content');
        $tcaFieldDefinitionCollection->addField($tcaFieldDefinition);

        return new TableDefinition(
            table: 'tt_content',
            capability: TableDefinitionCapability::createFromArray([]),
            typeField: 'CType',
            contentType: ContentType::CONTENT_ELEMENT,
            contentTypeDefinitionCollection: ContentTypeDefinitionCollection::createFromArray([], 'tt_content'),
            sqlColumnDefinitionCollection: SqlColumnDefinitionCollection::createFromArray([], 'tt_content'),
            tcaFieldDefinitionCollection: $tcaFieldDefinitionCollection,
            paletteDefinitionCollection: PaletteDefinitionCollection::createFromArray([], 'tt_content'),
            parentReferences: []
        );
    }
}
