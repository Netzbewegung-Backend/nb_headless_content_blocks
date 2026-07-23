<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\DataProcessing\ToArray;

use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray\TypolinkParameterToArray;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\LinkHandling\TypolinkParameter;

final class TypolinkParameterToArrayTest extends TestCase
{
    public function testEmptyUrlReturnsNull(): void
    {
        $typolinkParameter = new TypolinkParameter(url: '');

        $subject = new TypolinkParameterToArray($typolinkParameter);

        self::assertNull($subject->toArray());
    }

    public function testZeroUrlReturnsNull(): void
    {
        $typolinkParameter = new TypolinkParameter(url: '0');

        $subject = new TypolinkParameterToArray($typolinkParameter);

        self::assertNull($subject->toArray());
    }
}
