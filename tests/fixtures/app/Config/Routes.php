<?php
/**
 * Routes Configuration File
 */

return [
    // Example Module Hello Method
    'hello/([A-Za-z0-9]+)' => 'example/home/hello/$1',
    'say/hello/callback' => function() : string {
        return 'hello';
    },
    'say/hello' => ['\\Zewa\\Tests\\FixtureClass', 'hello']
];
