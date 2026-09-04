<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\ContentBlocks;

use Netzbewegung\NbHeadlessContentBlocks\ContentBlocks\HeadlessYamlLoader;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class HeadlessYamlLoaderTest extends UnitTestCase
{
    #[Test]
    public function returnsEmptyProcessingWithoutRegistry(): void
    {
        // container-less context: no ContentBlockRegistry, therefore no
        // headless.yaml support at all
        $subject = new HeadlessYamlLoader(null);

        self::assertSame([], $subject->getProcessingForContentBlock('test/filetest'));
    }
}
