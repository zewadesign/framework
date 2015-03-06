<?php
// Need to switch the hooks out for a proper events system.
// Then your loaded app (including modules) registers watchers
// and as the events are fired, the event system fires similar
// callbacks to watchers but as you add more watchers and event
// items you don't have to keep blowing up this array to maintain
// all the possible points of hooking the app even in to events
// and watchers in the modules n' whatnot.
$hooks = array('register' => (object) array());

$hooks['register']->preApplication = (object) array(
    'enabled' => true,
    'call'    => function () {

    }
);
$hooks['register']->postApplication = (object) array(
    'enabled' => true,
    'call'    => function () {

    }
);
$hooks['register']->preRegistry = (object) array(
    'enabled' => true,
    'call'    => function () {

    }
);
$hooks['register']->postRegistry = (object) array(
    'enabled' => true,
    'call'    => function () {

    }
);
$hooks['register']->preDatabase = (object) array(
    'enabled' => true,
    'call'    => function () {

    }
);
$hooks['register']->postDatabase = (object) array(
    'enabled' => true,
    'call'    => function () {

    }
);
$hooks['register']->preCache = (object) array(
    'enabled' => true,
    'call'    => function () {

    }
);
$hooks['register']->postCache = (object) array(
    'enabled' => true,
    'call'    => function () {

    }
);
$hooks['register']->preSession = (object) array(
    'enabled' => true,
    'call'    => function () {

    }
);
$hooks['register']->postSession = (object) array(
    'enabled' => true,
    'call'    => function () {

    }
);
$hooks['register']->preController = (object) array(
    'enabled' => true,
    'call'    => function () {

    }
);
$hooks['register']->postController = (object) array(
    'enabled' => true,
    'call'    => function () {

    }
);
$hooks['register']->preModel = (object) array(
    'enabled' => true,
    'call'    => function () {

    }
);
$hooks['register']->postModel = (object) array(
    'enabled' => true,
    'call'    => function () {

    }
);
