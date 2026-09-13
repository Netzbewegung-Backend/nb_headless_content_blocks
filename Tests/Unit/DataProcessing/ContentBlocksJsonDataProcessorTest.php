<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\DataProcessing;

use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ContentBlocksJsonDataProcessor;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ContentBlocksJsonDataProcessorTest extends UnitTestCase
{
    public function testHeadlessPhpWithoutReturnKeepsDataUnchanged(): void
    {
        // Tests run in the "Testing" context: like Production, a broken
        // headless.php degrades to the unmodified data.
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

    public function testHeadlessPhpWithoutReturnThrowsInDevelopmentContext(): void
    {
        $temporaryFile = (string)tempnam(sys_get_temp_dir(), 'nb_headless_test_');
        file_put_contents($temporaryFile, "<?php\n\$something = 1;\n");

        try {
            $this->withApplicationContext(new ApplicationContext('Development'), function () use ($temporaryFile): void {
                $this->expectException(\RuntimeException::class);
                $this->expectExceptionMessage('must return the (modified) data array');

                $this->includeLocalHeadlessPhp(['my_text' => 'value'], $temporaryFile);
            });
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

    /**
     * Environment::initialize() is the only (test-scoped) way to change the
     * application context; the previous context is restored afterwards.
     */
    private function withApplicationContext(ApplicationContext $context, \Closure $test): void
    {
        $previous = Environment::getContext();
        $this->initializeEnvironment($context);

        try {
            $test();
        } finally {
            $this->initializeEnvironment($previous);
        }
    }

    private function initializeEnvironment(ApplicationContext $context): void
    {
        Environment::initialize(
            $context,
            Environment::isCli(),
            Environment::isComposerMode(),
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            Environment::isWindows() ? 'WIN' : 'UNIX',
        );
    }
}
