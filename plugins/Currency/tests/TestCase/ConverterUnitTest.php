<?php

namespace Currency\Test\TestCase\Converter;

use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Currency\Converter;

/**
 * Class ConverterUnitTest
 * @package Currency\Test\TestCase\Converter
 */
class ConverterUnitTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.clearjunction/logger.logger_db',
        'plugin.currency.converter/rates',
    ];

    public static function setUpBeforeClass() {
        Plugin::load('Currency', ['autoload' => true]);
    }

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp() {
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown() {
        $this->fixtureManager->shutDown();
        parent::tearDown();
    }

    /**
     * Successful converting
     */
    public function testSuccess() {
        $amount = Converter::convert(10, 'EUR', 'USD');
        $this->assertEquals('15', $amount);
    }

    /**
     * Rate is missing
     */
    public function testRateMissing() {
        $this->expectExceptionMessage('No rate ZAR -> USD found');
        Converter::convert(10, 'ZAR', 'USD');
    }
}
