<?php

namespace VarunS\Flytrap\DBHandlers;

use VarunS\Flytrap\Security\NumberAlphaIdConverter;

require "../Computable.php";
use Computable;

class MoveAudioOperation implements Computable {
    protected int $fileId;
    protected string $newFolderId;
    protected int $convert;
    protected $alphaIdConversion;
    protected DBChecker $dbHandler;

    public function __construct($fileId, $newFolderId, $convert = 0, $dbHandler) {
        $this->fileId = $fileId;
        $this->newFolderId = $newFolderId;
        $this->convert = $convert;
        $this->alphaIdConversion = new NumberAlphaIdConverter();
        $this->dbHandler = $dbHandler;
    }

    public function compute() {
        $alphaIds = $this->convertToAlphaId();
        $audio = $this->selectAudioFile($alphaIds["file"]);
        $userId = mysqli_fetch_array($audio)[0];

        if ($this->checkAudioFileExists($audio)) {
            if ($this->checkUserOwnsAudioFile($userId))
                $this->updateAudioFileLocation($alphaIds["file"], $alphaIds["folder"]);
            else {
                return [
                    "statusCode" => 403,
                    "error" => [
                        "message" => "The audio was not moved because you do not own that audio file"
                    ]
                ];
            }
        } else {
            return [
                "statusCode" => 404,
                "error" => [
                    "message" => "That is not a valid ID"
                ]
            ];
        }

        if (mysqli_affected_rows($this->conn) == 1) {
            return [
                "statusCode" => 204
            ];
        } else {

        }
    }

    private function convertToAlphaId() {
        switch ($this->convert) {
            case '1':
                $fileAlpha = $this->alphaIdConversion->convertAlphaIdToNumericId($this->fileId);
                break;
            case '2':
                $fileAlpha = $this->alphaIdConversion->convertAlphaIdToNumericId($this->fileId);
                $newFolderAlpha = $this->alphaIdConversion->convertAlphaIdToNumericId($this->fileId);
                break;
            case '3':
                $newFolderAlpha = $this->alphaIdConversion->convertAlphaIdToNumericId($this->fileId);
                break;
            default:
                // If the query parameter is 0, it indicates going to the root directory
                $newFolderAlpha = $this->newFolderId != 0 ? $this->newFolderId : "NULL"; 
        }

        return [
            "folder" => $newFolderAlpha,
            "file" => $fileAlpha
        ];
    }

    private function selectAudioFile($fileAlpha) {
        return $this->dbHandler->executeQuery("SELECT user_id FROM audio_files WHERE id = " . $fileAlpha);
    }

    private function checkAudioFileExists($result) {
        return mysqli_num_rows($result) == 1;
    }

    private function checkUserOwnsAudioFile($audioFileOwner) {
        return $this->userId == $audioFileOwner;
    }

    private function updateAudioFileLocation($folderId, $fileId) {
        $this->dbHandler->executeQuery("UPDATE audio_files SET folder_id = $folderId WHERE id = $fileId");
    }
}

?>