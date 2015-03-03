<?php
// I'm sure there's a reason for this function but right now I'm missing it.
function addScripts($scripts = array()) {

    if($registeredScripts = \core\Registry::get('_scripts')) {
        foreach($scripts as $script) {
            // We've devised an entirely seperate function for adding scripts to the registry
            // but we don't check if the scripts exist
            array_push($registeredScripts, $script);

        }

        //  nor do we use the addJS method of the registry.
        \core\Registry::add('_scripts',$registeredScripts);
    } else {
        // nor do we verify this is an array or otherwise iterable value.
        \core\Registry::add('_scripts',$scripts);

    }
}
