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

        if ($this->dbChecker->lastQueryWasSuccessful()) {
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

        switch (true) {
            case $this->dbChecker->lastQueryGaveError():
                return [
                    "statusCode" => 500,
                    "error" => [
                        "message" => "A server error occurred and the audio could not be renamed"
                    ]
                ];    
            case $this->dbChecker->lastQueryAffectedNoRows():
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

    public function shareAudio($recipientEmail, $audioId) {
        $shareeIdQuery = $this->dbChecker->executeQuery("SELECT id FROM firstborumdatabase.users WHERE email = '$recipientEmail' LIMIT 1");
        
        if (mysqli_num_rows($shareeIdQuery) == 1) {
            $shareeId = mysqli_fetch_array($shareeIdQuery)[0];
            $this->dbChecker->executeQuery("INSERT INTO file_sharing (sharer_id, file_id, receiver_id) VALUES (" . $this->dbChecker->userId . ", $audioId, $shareeId)");
            if ($this->dbChecker->lastQueryWasSuccessful()) {
                return [
                    "statusCode" => 201,
                ];
            }
        } else {
            return [
                "statusCode" => 404,
                "error" => [
                    "message" => "That email doesn't exist."
                ]
            ];
        }


    }
}
