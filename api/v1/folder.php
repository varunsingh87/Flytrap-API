<?php

require __DIR__ . "/../../vendor/autoload.php";

use Flytrap\DBHandlers\FolderHandler;
use VarunS\PHPSleep\SimpleRest;

header("Access-Control-Allow-Headers: Authorization,authorization");
header("Access-Control-Expose-Headers: Authorization,authorization");
header("Access-Control-Allow-Origin: " . $_SERVER["HTTP_ORIGIN"]);
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");

$headers = apache_request_headers();

SimpleRest::handleHeaderValidation($headers, "authorization");

$folderHandler = new FolderHandler(SimpleRest::parseAuthorizationHeader($headers["authorization"]));

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $folderHandler->setFolderId($_GET['folder_id']);
        $response = $folderHandler->getFolder();
        SimpleRest::setHttpHeaders($response["statusCode"]);
        echo json_encode($response);
        break;
}

?>