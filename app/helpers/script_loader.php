<?php

function addScripts($scripts = array()) {

    if($registeredScripts = \core\Registry::get('_scripts')) {
        foreach($scripts as $script) {

            array_push($registeredScripts, $script);

        }


        \core\Registry::add('_scripts',$registeredScripts);
    } else {

        \core\Registry::add('_scripts',$scripts);

    }
}
