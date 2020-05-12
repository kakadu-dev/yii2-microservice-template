<?php

use Kakadu\Yii2Helpers\ActiveRecord\MysqlConnection;

return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache'  => [
            'class' => 'yii\caching\FileCache',
        ],
        'db'     => [
            'class'             => MysqlConnection::class,
            'charset'           => 'utf8',
            'host'              => env('MYSQL_HOST', 'mysql'),
            'port'              => env('MYSQL_PORT', 3306),
            'database'          => env('MYSQL_DATABASE', 'your_credentials'),
            'username'          => env('MYSQL_USER', 'root'),
            'password'          => env('MYSQL_PASSWORD', 'your_credentials'),
            'srv'               => env('MYSQL_SRV'),
            'enableSchemaCache' => YII_ENV_PROD,
            'slaveConfig'       => [
                'class'             => 'yii\db\Connection',
                'username'          => env('MYSQL_USER', 'root'),
                'password'          => env('MYSQL_PASSWORD'),
                'charset'           => 'utf8',
                'serverStatusCache' => false,
                'enableSchemaCache' => YII_ENV_PROD,
                'attributes'        => [
                    // PDO::ATTR_TIMEOUT
                    2 => 10,
                ],
            ],
            'slavesBalancers'   => env('MYSQL_SLAVES_BALANCER'),
        ],
        'mailer' => [
            'class'            => 'yii\swiftmailer\Mailer',
            'viewPath'         => '@common/mail',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
    ],
];
