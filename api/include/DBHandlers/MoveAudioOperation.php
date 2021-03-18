<?php

namespace Flytrap\DBHandlers;

use Flytrap\Security\NumberAlphaIdConverter;
use Flytrap\Computable;

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
        $this->alphaIdConversion = new NumberAlphaIdConverter(10);
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

        if ($this->dbHandler->lastQueryWasSuccessful()) {
            return [
                "statusCode" => 204
            ];
        } else {
            return [
                "statusCode" => 500,
                "error" => [
                    "message" => "An unknown server error occurred and the audio was not moved"
                ]
            ];
        }
    }

    private function convertToAlphaId() {
        switch ($this->convert) {
            case '1':
                $fileNumericId = $this->alphaIdConversion->convertAlphaIdToNumericId($this->fileId);
                break;
            case '2':
                $fileNumericId = $this->alphaIdConversion->convertAlphaIdToNumericId($this->fileId);
                $newFolderNumericId = $this->alphaIdConversion->convertAlphaIdToNumericId($this->fileId);
                break;
            case '3':
                $newFolderNumericId = $this->alphaIdConversion->convertAlphaIdToNumericId($this->fileId);
                break;
            default:
                // If the query parameter is 0, it indicates going to the root directory
                $newFolderNumericId = $this->newFolderId != 0 ? $this->newFolderId : "NULL"; 
        }

        return [
            "folder" => $newFolderNumericId,
            "file" => $fileNumericId
        ];
    }

    private function selectAudioFile($fileNumericId) {
        return $this->dbHandler->executeQuery("SELECT user_id FROM audio_files WHERE id = " . $fileNumericId);
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