<?php
// I'm not sure why these functions exist?
function baseURL($path = false) {
    if($path)
        return \core\Router::baseURL($path);

    return \core\Router::baseURL();
}

function currentURL() {
    return \core\Router::currentURL();
}


function currentURI() {
    return \core\Router::uri();
}