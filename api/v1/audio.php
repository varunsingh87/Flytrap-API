<?php

require __DIR__ . "/../../vendor/autoload.php";

use Flytrap\DBHandlers\AudioHandler;
use Flytrap\DBHandlers\FolderHandler;
use VarunS\PHPSleep\SimpleRest;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../../", "dbconfig.env");
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

$dbHandler = new AudioHandler(SimpleRest::parseAuthorizationHeader($headers["authorization"]));

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $dbHandler->setAudioId($_GET['audio_id']);
        $response = $dbHandler->getAudio();
        SimpleRest::setHttpHeaders($response["statusCode"]);
        
        echo json_encode($response);
        break;
    case 'POST':
        $dbHandler = new FolderHandler(SimpleRest::parseAuthorizationHeader($headers["authorization"]));
        $dbHandler->setFolderAlphaId($_GET['folder_alpha_id']);
        SimpleRest::handlePostParameterValidation("name");
        $response = $dbHandler->createNewAudio($_POST["name"]);
        SimpleRest::setHttpHeaders($response["statusCode"]);
        echo json_encode($response);
        break;
    case 'PUT':
        $put = [];
        parse_str(file_get_contents("php://input"), $put);
        $dbHandler->setAudioId($put['audio_id']);

        $response = $dbHandler->renameAudio($put['new_name']);
        SimpleRest::setHttpHeaders($response["statusCode"]);
        echo json_encode($response);
        break;
    case 'DELETE':
        parse_str(file_get_contents("php://input"), $GLOBALS["_{DELETE}"]);
        $dbHandler->setAudioId($GLOBALS["_{DELETE}"]['audio_id']);

        SimpleRest::handleDeleteParameterValidation("audio_id");

        $response = $dbHandler->deleteAudio();
        SimpleRest::setHttpHeaders($response["statusCode"]);
        echo json_encode($response);
        break;
}

?>