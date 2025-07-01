#!/usr/bin/env bash

set -e

if [ "$1" = "-s" ]; then
    shift
    TEST_SUITE=$1
    shift
fi

if [ -z "$TEST_SUITE" ]; then
    echo "Usage: $0 -s <suite>"
    echo "Suites: cgl, phpstan, unit, functional"
    exit 1
fi

# Change to project root
SCRIPT_DIR=$(dirname "$0")
PROJECT_ROOT=$(realpath "$SCRIPT_DIR/../../..")
cd "$PROJECT_ROOT"

# Ensure vendor directory exists
if [ ! -d ".Build/vendor" ]; then
    echo "Vendor directory not found. Please run 'composer install' first."
    exit 1
fi

# Set up test environment
export TYPO3_PATH_WEB=".Build/public"
export TYPO3_PATH_ROOT="$PROJECT_ROOT"
export TYPO3_PATH_TEMP=".Build/public/typo3temp"
export TYPO3_PATH_WEB=".Build/public"
export TYPO3_PATH_WEB_TESTS=".Build/public/typo3conf/ext/nb_headless_content_blocks/Tests"
export TYPO3_PATH_WEB_TESTS_BASE=".Build/public/typo3conf/ext/nb_headless_content_blocks/Tests/Base"
export TYPO3_PATH_WEB_TESTS_UNIT=".Build/public/typo3conf/ext/nb_headless_content_blocks/Tests/Unit"
export TYPO3_PATH_WEB_TESTS_FUNCTIONAL=".Build/public/typo3conf/ext/nb_headless_content_blocks/Tests/Functional"

# Create necessary directories
mkdir -p ".Build/public/typo3temp"
mkdir -p ".Build/public/typo3conf/ext/nb_headless_content_blocks/Tests/Unit"
mkdir -p ".Build/public/typo3conf/ext/nb_headless_content_blocks/Tests/Functional"

# Run the selected test suite
case "$TEST_SUITE" in
cgl)
    echo "Running Code Guidelines checks..."
    vendor/bin/php-cs-fixer fix --dry-run --diff --verbose
    ;;
phpstan)
    echo "Running PHPStan analysis..."
    vendor/bin/phpstan analyse --level=max src Tests
    ;;
unit)
    echo "Running Unit Tests..."
    vendor/bin/phpunit --configuration Tests/Unit/UnitTests.xml
    ;;
functional)
    echo "Running Functional Tests..."
    vendor/bin/phpunit --configuration Tests/Functional/FunctionalTests.xml
    ;;
*)
    echo "Unknown test suite: $TEST_SUITE"
    exit 1
    ;;
esac
