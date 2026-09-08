<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\Schema;

use Netzbewegung\NbHeadlessContentBlocks\Schema\SchemaEndpointAccess;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SchemaEndpointAccessTest extends UnitTestCase
{
    #[Test]
    public function isAvailableInDevelopmentContextsByDefault(): void
    {
        self::assertTrue(SchemaEndpointAccess::isAllowed(true, false, '', ''));
    }

    #[Test]
    public function isBlockedOutsideDevelopmentWithoutTheSetting(): void
    {
        self::assertFalse(SchemaEndpointAccess::isAllowed(false, false, '', ''));
    }

    #[Test]
    public function isAvailableEverywhereWhenEnabled(): void
    {
        self::assertTrue(SchemaEndpointAccess::isAllowed(false, true, '', ''));
        self::assertTrue(SchemaEndpointAccess::isAllowed(true, true, '', ''));
    }

    #[Test]
    public function configuredTokenIsRequiredInAnyContext(): void
    {
        self::assertFalse(SchemaEndpointAccess::isAllowed(false, false, 'secret', ''));
        self::assertFalse(SchemaEndpointAccess::isAllowed(false, true, 'secret', 'wrong'));
        self::assertFalse(SchemaEndpointAccess::isAllowed(true, true, 'secret', 'wrong'));
        self::assertTrue(SchemaEndpointAccess::isAllowed(false, true, 'secret', 'secret'));
        self::assertTrue(SchemaEndpointAccess::isAllowed(true, false, 'secret', 'secret'));
    }
}
