<?php

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

/**
 * This file is defined in FunctionalTests.xml and called by phpunit
 * before instantiating the test suites.
 */
(static function () {
    $testbase = new \TYPO3\TestingFramework\Core\Testbase();
    $testbase->defineOriginalRootPath();
    $testbase->createDirectory(ORIGINAL_ROOT . 'var/tests');
    $testbase->createDirectory(ORIGINAL_ROOT . 'var/transient');

    $packagesOfInterest = [
        'typo3/cms-core',
        'friendsoftypo3/content-blocks',
        'friendsoftypo3/headless',
        'b13/container',
    ];
    $composerLockPath = ORIGINAL_ROOT . '../../composer.lock';
    if (file_exists($composerLockPath)) {
        $lockData = json_decode((string)file_get_contents($composerLockPath), true);
        $allPackages = array_merge($lockData['packages'] ?? [], $lockData['packages-dev'] ?? []);
        $versions = [];
        foreach ($allPackages as $package) {
            if (in_array($package['name'] ?? '', $packagesOfInterest, true)) {
                $versions[$package['name']] = $package['version'] ?? 'unknown';
            }
        }
        $log = "\n=== Package Versions ===\n";
        foreach ($packagesOfInterest as $name) {
            $log .= $name . ': ' . ($versions[$name] ?? 'not installed') . "\n";
        }
        $log .= "========================\n\n";
        file_put_contents(ORIGINAL_ROOT . '../../functional-test.log', $log);
    }
})();
