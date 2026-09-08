<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Functional\Schema;

use Netzbewegung\NbHeadlessContentBlocks\Schema\JsonSchemaGenerator;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Delivery guard: the committed combined schema artifact
 * (Tests/Functional/Schema/Fixtures/content-blocks.schema.json) must be
 * byte-identical to what the generator produces for the fixture Content
 * Blocks. If this test fails, it has already rewritten the artifact —
 * review the diff and commit it.
 *
 * The artifact freezes the shape of the generated schemas (property
 * names, type mappings, definitions, the sorted oneOf branches) beyond
 * what the contract tests check, and can be published directly as a
 * schema sample.
 */
final class CommittedSchemaArtifactTest extends FunctionalTestCase
{
    private const ARTIFACT_PATH = __DIR__ . '/Fixtures/content-blocks.schema.json';

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/nb_headless_content_blocks/Tests/Fixtures/Extensions/test_nb_headless_content_blocks',
        'typo3conf/ext/container',
        'typo3conf/ext/content_blocks',
        'typo3conf/ext/nb_headless_content_blocks',
    ];

    #[Test]
    public function committedArtifactMatchesTheGeneratorOutput(): void
    {
        $generated = $this->get(JsonSchemaGenerator::class)->generateCombined();
        $generatedJson = json_encode(
            $generated,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        ) . LF;

        $committedJson = file_exists(self::ARTIFACT_PATH)
            ? (string)file_get_contents(self::ARTIFACT_PATH)
            : '';

        if ($generatedJson !== $committedJson) {
            file_put_contents(self::ARTIFACT_PATH, $generatedJson);

            self::fail(
                'The committed schema artifact did not match the generated schema and has been'
                . ' rewritten (' . self::ARTIFACT_PATH . '). Review the diff and commit it.'
            );
        }

        self::assertSame($generatedJson, $committedJson);
    }
}
