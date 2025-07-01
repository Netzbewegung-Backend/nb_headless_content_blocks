<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\ToArray;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TypolinkParameterToArrayTest extends UnitTestCase
{
    #[Test]
    public function firstTest(): void
    {
        echo '###FIRST TEST###';
        $x = 1;
        self::assertSame(0, $x);
    }
}