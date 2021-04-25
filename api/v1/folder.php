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
SimpleRest::handleRequestMethodValidation("GET", "POST", "OPTIONS");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") exit();

$headers = apache_request_headers();

SimpleRest::handleHeaderValidation($headers, "authorization");

$folderHandler = new FolderHandler(SimpleRest::parseAuthorizationHeader($headers["authorization"]));

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $folderHandler->setFolderAlphaId($_GET['folder_id']);

        $response = [
            "root" => $folderHandler->getFolderInfo()
        ];

        $excludeAudio = $_GET['exclude_audio'] ?? false;
        $excludeFolder = $_GET['exclude_folder'] ?? false;

        switch (true) {
            // Include both
            case !$excludeAudio && !$excludeFolder:
                $response['folder'] = $folderHandler->getFolderSubdirectories();
                $response['audio'] = $folderHandler->getFolderAudioFiles();

                if ($response['folder']['statusCode'] === $response['audio']['statusCode']) {
                    $response['statusCode'] = $response['folder']['statusCode'];
                }
                else {
                    $response['statusCode'] = 200;
                }

                break;

            // Include folder ONLY
            case $excludeAudio && !$excludeFolder:
                $response['folder'] = $folderHandler->getFolderSubdirectories();
                $response["statusCode"] = $response['folder']['statusCode'];
                break;

            // Include audio ONLY
            case $excludeFolder && !$excludeAudio:
                $response['audio'] = $folderHandler->getFolderAudioFiles();
                $response["statusCode"] = $response['audio']['statusCode'];
                break;

            // Include neither
            default:
                $response["statusCode"] = 201;
                break;

        }

        SimpleRest::setHttpHeaders($response["statusCode"]);
        echo json_encode($response);

        break;
    case 'POST':
        $folderHandler->setFolderAlphaId($_POST["parent_id"]);
        $response = $folderHandler->createNewFolder($_POST["name"]);

        SimpleRest::setHttpHeaders($response["statusCode"]);
        echo json_encode($response);
        break;
}

?>