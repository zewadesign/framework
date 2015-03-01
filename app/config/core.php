<?php

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

            'loginRedirect' => 'user/account/login', // redirect to a different module for verification or whatever.
            'baseRedirect' => 'user/account/settings',
            'defaultController' => 'home',
            'defaultMethod' => 'index',
//            '404' => 'admin/404',
//            'noAccess' => 'admin/noaccess'

        ),
    ),
    'hooks' => true,
    'acl' => false,
    /*array( // if acl is enabled from core, we can use acl
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
