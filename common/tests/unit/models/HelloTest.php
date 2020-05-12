<?php

namespace common\tests\unit\models;

/**
 * Hello test
 */
class HelloTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;


    public function testWorldCorrect()
    {
        expect('hello world test', true)->true();
    }
}
