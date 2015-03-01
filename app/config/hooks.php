<?php

$hooks = array('register' => (object)array());

$hooks['register']->preApplication = (object) array(
    'enabled' => true,
    'call' => function(){

    }
);
$hooks['register']->postApplication = (object) array(
    'enabled' => true,
    'call' => function(){

    }
);
$hooks['register']->preRegistry = (object) array(
    'enabled' => true,
    'call' => function(){

    }
);
$hooks['register']->postRegistry = (object) array(
    'enabled' => true,
    'call' => function(){

    }
);
$hooks['register']->preDatabase = (object) array(
    'enabled' => true,
    'call' => function(){

    }
);
$hooks['register']->postDatabase = (object) array(
    'enabled' => true,
    'call' => function(){

    }
);
$hooks['register']->preCache = (object) array(
    'enabled' => true,
    'call' => function(){

    }
);
$hooks['register']->postCache = (object) array(
    'enabled' => true,
    'call' => function(){

    }
);
$hooks['register']->preSession = (object) array(
    'enabled' => true,
    'call' => function(){

    }
);
$hooks['register']->postSession = (object) array(
    'enabled' => true,
    'call' => function(){

    }
);
$hooks['register']->preController = (object) array(
    'enabled' => true,
    'call' => function(){

    }
);
$hooks['register']->postController = (object) array(
    'enabled' => true,
    'call' => function(){

    }
);
$hooks['register']->preModel = (object) array(
    'enabled' => true,
    'call' => function(){

    }
);
$hooks['register']->postModel = (object) array(
    'enabled' => true,
    'call' => function(){

    }
);