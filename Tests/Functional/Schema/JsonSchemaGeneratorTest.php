<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Functional\Schema;

use Netzbewegung\NbHeadlessContentBlocks\Schema\JsonSchemaGenerator;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class JsonSchemaGeneratorTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/nb_headless_content_blocks/Tests/Fixtures/Extensions/test_nb_headless_content_blocks',
        'typo3conf/ext/container',
        'typo3conf/ext/content_blocks',
        'typo3conf/ext/nb_headless_content_blocks',
    ];

    #[Test]
    public function contentElementTypeNamesAreExposed(): void
    {
        $typeNames = $this->get(JsonSchemaGenerator::class)->getContentElementTypeNames();

        self::assertContains('test_simple', $typeNames);
        self::assertContains('test_filetest', $typeNames);
        self::assertContains('test_yamledges', $typeNames);
    }

    #[Test]
    public function simpleBlockSchemaContainsAllFields(): void
    {
        $schema = $this->get(JsonSchemaGenerator::class)->generateForTypeName('test_simple');

        self::assertSame('https://json-schema.org/draft/2020-12/schema', $schema['$schema']);
        self::assertSame('test/simple', $schema['title']);
        self::assertSame(
            ['bodytext', 'header', 'my_categories', 'my_checkbox', 'my_collection', 'my_datetime', 'my_json', 'my_link', 'my_number', 'my_password', 'my_select', 'my_text'],
            array_keys($schema['properties'])
        );
    }

    #[Test]
    public function simpleBlockSchemaMapsFieldTypes(): void
    {
        $schema = $this->get(JsonSchemaGenerator::class)->generateForTypeName('test_simple');
        $properties = $schema['properties'];

        self::assertSame(['type' => ['string', 'null']], $properties['my_text']);
        self::assertSame(['type' => ['number', 'null']], $properties['my_number']);
        self::assertSame(['type' => ['integer', 'null']], $properties['my_checkbox']);
        self::assertSame(['type' => ['string', 'null'], 'format' => 'date-time'], $properties['my_datetime']);
        self::assertSame(['const' => ''], $properties['my_password']);
        self::assertSame(['type' => ['object', 'array', 'null']], $properties['my_json']);
        self::assertSame(
            ['anyOf' => [['type' => 'string'], ['type' => 'array', 'items' => ['type' => 'string']]]],
            $properties['my_select']
        );
        self::assertSame(
            ['anyOf' => [['$ref' => '#/$defs/linkObject'], ['type' => 'null']]],
            $properties['my_link']
        );
        self::assertSame(
            ['type' => 'array', 'items' => ['$ref' => '#/$defs/categoryObject']],
            $properties['my_categories']
        );
    }

    #[Test]
    public function collectionFieldReferencesRecordDefinition(): void
    {
        $schema = $this->get(JsonSchemaGenerator::class)->generateForTypeName('test_simple');
        $itemRef = $schema['properties']['my_collection']['items']['$ref'];

        self::assertStringStartsWith('#/$defs/record_', $itemRef);

        $definitionKey = substr($itemRef, strlen('#/$defs/'));
        self::assertArrayHasKey($definitionKey, $schema['$defs']);
        self::assertSame(['type' => ['string', 'null']], $schema['$defs'][$definitionKey]['properties']['text']);
    }

    #[Test]
    public function fileFieldSchemaDependsOnRelationship(): void
    {
        $schema = $this->get(JsonSchemaGenerator::class)->generateForTypeName('test_filetest');
        $properties = $schema['properties'];

        self::assertSame(
            ['$ref' => '#/$defs/file_test_filetest_my_image'],
            $properties['my_image']['anyOf'][0] ?? []
        );
        self::assertSame('array', $properties['my_images']['type'] ?? null);
    }

    #[Test]
    public function headlessYamlVariantsBecomeConcreteThumbnailProperties(): void
    {
        $schema = $this->get(JsonSchemaGenerator::class)->generateForTypeName('test_filetest');

        // oneToOne field "my_image": mobile + desktop declared in headless.yaml
        $imageThumbnails = $schema['$defs']['file_test_filetest_my_image']['properties']['thumbnails'];
        self::assertSame(['desktop', 'mobile'], array_keys($imageThumbnails['properties']));
        self::assertSame(['type' => 'string'], $imageThumbnails['properties']['mobile']);
        // additionalProperties stays open: TypoScript may add more variants
        self::assertSame(['type' => 'string'], $imageThumbnails['additionalProperties']);

        // oneToMany field "my_images": only mobile declared
        $imagesThumbnails = $schema['$defs']['file_test_filetest_my_images']['properties']['thumbnails'];
        self::assertSame(['mobile'], array_keys($imagesThumbnails['properties']));

        // the shared loose fileObject is still shipped for fields without variants
        self::assertSame(
            ['type' => 'object', 'additionalProperties' => ['type' => 'string']],
            $schema['$defs']['fileObject']['properties']['thumbnails']
        );
    }

    #[Test]
    public function containerChildrenFromHeadlessYamlBecomeRecursiveElementLists(): void
    {
        $schema = $this->get(JsonSchemaGenerator::class)->generateForTypeName('test_containerblock');

        self::assertSame(
            ['type' => 'array', 'items' => ['$ref' => '#/$defs/contentBlockElement']],
            $schema['properties']['main']
        );

        // the recursive element definition is shipped with the block schema
        $typeConstants = [];
        foreach ($schema['$defs']['contentBlockElement']['oneOf'] as $branch) {
            $typeConstants[] = $branch['properties']['type']['const'];
        }
        self::assertContains('test_containerblock', $typeConstants);
        self::assertContains('test_simple', $typeConstants);
    }

    #[Test]
    public function combinedSchemaDiscriminatesByType(): void
    {
        $schema = $this->get(JsonSchemaGenerator::class)->generateCombined();

        self::assertSame('#/$defs/contentBlockElement', $schema['$ref']);
        $typeConstants = [];
        foreach ($schema['$defs']['contentBlockElement']['oneOf'] as $branch) {
            $typeConstants[] = $branch['properties']['type']['const'];
        }
        self::assertContains('test_simple', $typeConstants);
        self::assertContains('test_filetest', $typeConstants);

        foreach (['linkObject', 'fileObject', 'errorObject', 'categoryObject', 'contentBlockElement'] as $sharedDefinition) {
            self::assertArrayHasKey($sharedDefinition, $schema['$defs']);
        }
    }

    #[Test]
    public function tcaTypesWithoutContentBlockBecomeFallbackBranches(): void
    {
        $schema = $this->get(JsonSchemaGenerator::class)
            ->generateCombined('', ['html', 'shortcut', 'test_simple', '1']);

        $branchesByType = [];
        foreach ($schema['$defs']['contentBlockElement']['oneOf'] as $branch) {
            $branchesByType[$branch['properties']['type']['const']] = $branch;
        }

        // TCA's internal default record type is never delivered as element
        self::assertArrayNotHasKey('1', $branchesByType);
        // Content Block types keep their typed data schema
        self::assertSame(
            ['$ref' => '#/$defs/ctype_test_simple'],
            $branchesByType['test_simple']['properties']['data']
        );
        // non-Content-Block types get the loose fallback envelope
        foreach (['html', 'shortcut'] as $fallbackType) {
            self::assertSame(['type' => 'object'], $branchesByType[$fallbackType]['properties']['data']);
            self::assertStringContainsString(
                sprintf('"%s"', $fallbackType),
                $branchesByType[$fallbackType]['description']
            );
        }
    }

    #[Test]
    public function unknownTypeNameReturnsNull(): void
    {
        self::assertNull(
            $this->get(JsonSchemaGenerator::class)->generateForTypeName('unknown_ctype')
        );
    }
}
