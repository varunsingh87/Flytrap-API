<?php

chdir(__DIR__);

if (php_sapi_name() !== 'cli-server') {
    die('this is only for the php development server');
}

// Serve as asset file from filesystem if non-PHP file and not root
if (preg_match('/\.(?:png|jpg|jpeg|gif|css|html|json|md|txt)$/', $_SERVER["REQUEST_URI"])) {
    return false;    // serve the requested resource as-is.
}

function rewrite($url, $file) {
    if (str_contains($_SERVER["REQUEST_URI"], $url)) {
        include __DIR__ . DIRECTORY_SEPARATOR . $file;
        exit();
    }
}

rewrite("/v1/audio/location", "api/v1/audio/location.php");
rewrite("/v1/audio/collaboration", "api/v1/audio/collaboration.php");
rewrite("/v1/audio", "api/v1/audio.php");
rewrite("/v1/folder/location", "api/v1/folder/location.php");
rewrite("/v1/folder/collaboration", "api/v1/folder/collaboration.php");
rewrite("/v1/folder", "api/v1/folder.php");

include __DIR__ . DIRECTORY_SEPARATOR . "routes.html";

?>

