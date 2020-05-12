<?php

namespace common\tests\unit\models;

//use common\fixtures\UserFixture;

/**
 * Example test
 */
class ExampleTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;


    /**
     * @return array
     */
    public function _fixtures()
    {
//        return [
//            'user' => [
//                'class' => UserFixture::className(),
//                'dataFile' => codecept_data_dir() . 'user.php'
//            ]
//        ];
    }

    public function testExampleCorrect()
    {
        expect('example test', true)->true();
    }

    public function testHelloCorrect()
    {
        expect('example hello', false)->false();
    }
}
