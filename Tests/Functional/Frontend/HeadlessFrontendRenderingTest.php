<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\ContentBlocks\Tests\Functional\Frontend;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class HeadlessFrontendRenderingTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected array $testExtensionsToLoad = [
        #'typo3conf/ext/content_blocks/Tests/Fixtures/Extensions/test_content_blocks_b',
        #'typo3conf/ext/content_blocks/Tests/Fixtures/Extensions/test_content_blocks_c',
        'typo3conf/ext/headless',
        'typo3conf/ext/content_blocks',
        'typo3conf/ext/nb_headless_content_blocks',
    ];
    
    #[Test]
    public function exampleTest(): void
    {
        self::assertSame(0, 1);
    }
}