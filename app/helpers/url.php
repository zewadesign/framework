<?php

function cdnURL($path = false) {

    return 'http://cdn-01.iimdevelopment.net/marketplace/'.$path;
}

function baseURL($path = false) {
    if($path)
        return \core\Registry::baseURL($path);

    return \core\Registry::baseURL();
}

function currentURL() {
    return \core\Registry::currentURL();
}