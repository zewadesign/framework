<?php
// If anything should gets comments, the config file should.
// This needs to be broken up so it's more readable.
// The use of PHP 5.3 array construct syntax is definitely distracting.
$core = array(
    'autoload' => array(
        'helpers' => array(
            'url',
            'script_loader'
        ),
        'libraries' => array(

        )
    ),
    'language' => 'en_lang',
    'modules' => array(
        'defaultModule' => 'example',
        'example' => array(
            'aclRedirect' => 'user/account/login', // redirect to a different module for verification or whatever.
            'defaultController' => 'home',
            'defaultMethod' => 'index',
//            '404' => 'admin/404',
//            'noAccess' => 'admin/noaccess'
        ),
    ),
    'hooks' => true,
    'acl' => false,
    /*(object) ['roles' => (object) ['guest' => 1, 'admin' => 2, 'client' => 3, 'user' => 4]];
        'guestId' => 1,
        'adminId' => 2,
        'clientId' => 3,
        'userId' => 4
    ),*/
    'session' => false,
    'cache' => false,
        /*array(
            'host' => 'localhost',
            'port' => '11111'
        )*/
    'database' => false
        /*array(
            'default' => array( // name your db, as the index you can select multiple dbs from database layer
                'dsn' => 'mysql:host=localhost;dbname=database',
                'user' => 'database',
                'pass' => 'database'
            ),
        )*/
);
