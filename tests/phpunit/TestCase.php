<?php
/**
 * Base test case for Simple Contact plugin.
 *
 * @package SimpleContact\Tests
 * @since 1.0.0
 */

namespace SimpleContact\Tests;

use Brain\Monkey;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Class TestCase
 *
 * Provides Brain Monkey bootstrapping for plugin tests.
 *
 * @since 1.0.0
 */
abstract class TestCase extends PHPUnitTestCase {
        /**
         * Sets up the test case.
         *
         * @since 1.0.0
         *
         * @return void
         */
        protected function setUp(): void {
                parent::setUp();
                Monkey\setUp();
        }

        /**
         * Tears down the test case.
         *
         * @since 1.0.0
         *
         * @return void
         */
        protected function tearDown(): void {
                Monkey\tearDown();
                parent::tearDown();
        }
}
