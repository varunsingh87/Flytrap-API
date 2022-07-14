<?php

require __DIR__ . "/../../vendor/autoload.php";

use Flytrap\DBHandlers\FolderHandler;
use VarunS\PHPSleep\SimpleRest;

header("Access-Control-Allow-Headers: Authorization,authorization");
header("Access-Control-Expose-Headers: Authorization,authorization");

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../../", "dbconfig.env");
$dotenv->safeLoad();

if (isset($_SERVER["HTTP_ORIGIN"])) {
    header("Access-Control-Allow-Origin: " . $_SERVER["HTTP_ORIGIN"] . "");
} else {
    $_SERVER["HTTP_ORIGIN"] = "localhost:8100";
}

header("Access-Control-Allow-Credentials: true");
SimpleRest::handleRequestMethodValidation("GET", "POST", "PUT", "DELETE", "OPTIONS");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") exit();

$headers = apache_request_headers();

SimpleRest::handleHeaderValidation($headers, "authorization");

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}, E_WARNING);

$folderHandler = new FolderHandler(SimpleRest::parseAuthorizationHeader($headers["authorization"]));

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $folderHandler->setFolderAlphaId($_GET['folder_id']);

            SimpleRest::setHttpHeaders(200);
            echo json_encode([
                "statusCode" => 200,
                "root" => $folderHandler->getFolderInfo(),
                "folder" => $folderHandler->getFolderSubdirectories(),
                "audio" => $folderHandler->getFolderAudioFiles()
            ]);

            break;
        case 'PUT':
            $put = [];
            parse_str(file_get_contents("php://input"), $put);

            $folderHandler->setFolderAlphaId($put['id']);
            $response = $folderHandler->renameFolder($put['new_name']);

            SimpleRest::setHttpHeaders($response["statusCode"]);
            echo json_encode($response);

            break;
        case 'POST':
            $folderHandler->setFolderAlphaId($_POST["parent_id"]);
            $response = $folderHandler->createNewFolder($_POST["name"]);

            SimpleRest::setHttpHeaders($response["statusCode"]);
            echo json_encode($response);
            break;
        case 'DELETE':
            $delete = [];
            parse_str(file_get_contents("php://input"), $delete);

            $folderHandler->setFolderAlphaId($delete["folder_id"]);

            $response = $folderHandler->deleteFolder();

            SimpleRest::setHttpHeaders($response["statusCode"]);
            echo json_encode($response);
            break;
        default:
            SimpleRest::setHttpHeaders(405);
            echo json_encode(["message" => "Method not allowed"]);
            break;
    }
} catch (\Throwable $e) {
    SimpleRest::setHttpHeaders(500);
    echo json_encode([
        "statusCode" => 500,
        "message" => $e->getMessage()
    ]);
}
