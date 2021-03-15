<?php

namespace VarunS\Flytrap\DBHandlers;

class AudioHandler
{
    protected UserChecker $dbChecker;

    function __construct($userApiKey)
    {
        $this->dbChecker = new UserChecker($userApiKey);
    }

    public function deleteAudio($audioId) {
        $this->dbChecker->executeQuery("DELETE FROM audio_files WHERE user_id = " . $this->userId . " AND id = $audioId");

        if (mysqli_affected_rows($this->conn) == 1) {
            return [
                "statusCode" => 204
            ];
        } else {
            return [
                "statusCode" => 404,
                "error" => [
                    "message" => "The file could not be found"
                ]
            ];
        }
    }

    public function renameAudio($audioId, $newName) {
        $this->dbChecker->executeQuery("UPDATE audio_files SET file_name = '$newName' WHERE id = $audioId AND user_id = " . $this->userId);

        $affectedRowsOfQuery = mysqli_affected_rows($this->conn);
        switch (true) {
            case $affectedRowsOfQuery == -1:
                return [
                    "statusCode" => 500,
                    "error" => [
                        "message" => "A server error occurred and the audio could not be renamed"
                    ]
                ];    
            case $affectedRowsOfQuery == 0:
                return [
                    "statusCode" => 404,
                    "error" => [
                        "message" => "The audio was not renamed because you do not own that audio file"
                    ]
                ];
            default: 
                return [
                    "statusCode" => 204
                ];
        }
    }

    public function moveAudio($fileId, $newFolderId, $convert = FALSE) {
        $moveAudioOp = new MoveAudioOperation($fileId, $newFolderId, $convert, $this->dbChecker);
        return $moveAudioOp->compute();
    }
}
