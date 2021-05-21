<?php
# https://api.audio.borumtech.com/v1/folder/collaboration
# POST to share with a new person
# DELETE to remove a sharee
# GET to see sharees

require __DIR__ . "/../../vendor/autoload.php";

use Flytrap\DBHandlers\FolderHandler;
use VarunS\PHPSleep\SimpleRest;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, "dbconfig.env");
$dotenv->safeLoad();

header("Access-Control-Allow-Headers: Authorization,authorization");
header("Access-Control-Expose-Headers: Authorization,authorization");
header("Access-Control-Allow-Credentials: true");

if (isset($_SERVER["HTTP_ORIGIN"])) {
    header("Access-Control-Allow-Origin: " . $_SERVER["HTTP_ORIGIN"]);
} else {
    $_SERVER["HTTP_ORIGIN"] = "localhost";
}

SimpleRest::handleRequestMethodValidation("GET", "POST", "PUT", "DELETE", "OPTIONS");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") exit();

$headers = apache_request_headers();

SimpleRest::handleHeaderValidation($headers, "authorization");

$dbHandler = new FolderHandler(SimpleRest::parseAuthorizationHeader($headers["authorization"]));

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // $dbHandler->setFolderId($_GET['audio_id']);
        // $response = $dbHandler->getFolder();
        // SimpleRest::setHttpHeaders($response["statusCode"]);
        
        // echo json_encode($response);
        break;
    case 'POST':
        SimpleRest::handlePostParameterValidation("recipient_email", "folder_id");
        $dbHandler->setFolderAlphaId($_POST["folder_id"]);
        $response = $dbHandler->shareFolder($_POST["recipient_email"]);
        
        SimpleRest::setHttpHeaders($response["statusCode"]);
        echo json_encode($response);
        break;
    case 'PUT':
        // $put = [];
        // parse_str(file_get_contents("php://input"), $put);
        // $dbHandler->setFolderId($put['audio_id']);
        // $response = $dbHandler->renameFolder($put['new_name']);
        // SimpleRest::setHttpHeaders($response["statusCode"]);
        // echo json_encode($response);
        break;
    case 'DELETE':
        // parse_str(file_get_contents("php://input"), $GLOBALS["_{DELETE}"]);
        // $dbHandler->setFolderId($GLOBALS["_{DELETE}"]['audio_id']);

        // SimpleRest::handleDeleteParameterValidation("audio_id");

        // $response = $dbHandler->deleteFolder();
        // SimpleRest::setHttpHeaders($response["statusCode"]);
        // echo json_encode($response);
        break;
}

?>