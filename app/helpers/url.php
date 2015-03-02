<?php

function baseURL($path = false) {
    if($path)
        return \core\Registry::baseURL($path);

    return \core\Registry::baseURL();
}

function currentURL() {
    return \core\Registry::currentURL();
}