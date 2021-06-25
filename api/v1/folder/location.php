<?php

require __DIR__ . "/../../../vendor/autoload.php";

use Flytrap\DBHandlers\FolderHandler;
use VarunS\PHPSleep\SimpleRest;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, "/../../../dbconfig.env");
$dotenv->safeLoad();

header("Access-Control-Allow-Headers: Authorization,authorization");
header("Access-Control-Expose-Headers: Authorization,authorization");
header("Access-Control-Allow-Credentials: true");

if (isset($_SERVER["HTTP_ORIGIN"])) {
    header("Access-Control-Allow-Origin: " . $_SERVER["HTTP_ORIGIN"]);
} else {
    $_SERVER["HTTP_ORIGIN"] = "localhost";
}

SimpleRest::handleRequestMethodValidation("PUT", "OPTIONS");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") exit();

$headers = apache_request_headers();

SimpleRest::handleHeaderValidation($headers, "authorization");

$dbHandler = new FolderHandler(SimpleRest::parseAuthorizationHeader($headers["authorization"]));

switch ($_SERVER['REQUEST_METHOD']) {
    case 'PUT':
        $put = [];
        parse_str(file_get_contents("php://input"), $put);
        $dbHandler->setFolderAlphaId($put['folder_id']);
        $response = $dbHandler->moveFolder($put['new_folder_id']);
        SimpleRest::setHttpHeaders($response["statusCode"]);
        echo json_encode($response);
        break;
}

?>