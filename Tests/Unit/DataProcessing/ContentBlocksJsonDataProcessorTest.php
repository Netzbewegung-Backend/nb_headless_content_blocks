<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\DataProcessing;

use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ContentBlocksJsonDataProcessor;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ContentBlocksJsonDataProcessorTest extends UnitTestCase
{
    public function testHeadlessPhpWithoutReturnKeepsDataUnchanged(): void
    {
        $data = ['my_text' => 'value'];

        $temporaryFile = (string)tempnam(sys_get_temp_dir(), 'nb_headless_test_');
        file_put_contents($temporaryFile, "<?php\n\$something = 1;\n");

        try {
            $result = $this->includeLocalHeadlessPhp($data, $temporaryFile);

            self::assertSame($data, $result);
        } finally {
            unlink($temporaryFile);
        }
    }

    public function testHeadlessPhpReturningArrayIsUsedAsIs(): void
    {
        $data = ['my_text' => 'value'];

        $temporaryFile = (string)tempnam(sys_get_temp_dir(), 'nb_headless_test_');
        file_put_contents($temporaryFile, "<?php\nreturn \$data + ['added' => true];\n");

        try {
            $result = $this->includeLocalHeadlessPhp($data, $temporaryFile);

            self::assertSame(['my_text' => 'value', 'added' => true], $result);
        } finally {
            unlink($temporaryFile);
        }
    }

    /**
     * The method under test touches no constructor dependencies, so the
     * processor is instantiated without the (container-only) constructor.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function includeLocalHeadlessPhp(array $data, string $headlessPhpFile): array
    {
        $reflection = new \ReflectionClass(ContentBlocksJsonDataProcessor::class);
        $subject = $reflection->newInstanceWithoutConstructor();

        $result = $reflection->getMethod('includeLocalHeadlessPhp')->invoke($subject, $data, $headlessPhpFile);

        self::assertIsArray($result);

        return $result;
    }
}
