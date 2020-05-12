<?php

return [
    'Development' => [
        'path'          => 'dev',
        'setWritable'   => [
            'console/runtime',
        ],
        'setExecutable' => [
            'yii',
            'yii_test',
        ],
    ],
    'Production'  => [
        'path'          => 'prod',
        'setWritable'   => [
            'console/runtime',
        ],
        'setExecutable' => [
            'yii',
        ],
    ],
];
