<?php

namespace Flytrap\DBHandlers;

use Flytrap\EndpointResponse;

class AudioHandler
{
    protected UserChecker $dbChecker;
    protected $audioId;

    function __construct($userApiKey)
    {
        $this->dbChecker = new UserChecker($userApiKey);
    }

    /**
     * Setter method for $audioId
     * so it can be retrieved after instantiation through different request methods
     */
    public function setAudioId($audioId) {
        $this->audioId = $audioId;
    }

    public function getAudio() {
        $audioFile = $this->dbChecker->executeQuery("SELECT * FROM audio_files WHERE id = " . $this->audioId);
        $audioFile = mysqli_fetch_assoc($audioFile);

        return EndpointResponse::outputSuccessWithData($audioFile);
    }

    public function deleteAudio()
    {
        $this->dbChecker->executeQuery("DELETE FROM audio_files WHERE user_id = " . $this->dbChecker->userId . " AND id = " . $this->audioId);

        if ($this->dbChecker->lastQueryWasSuccessful()) {
            return EndpointResponse::outputSuccessWithoutData();
        } else {
            return [
                "statusCode" => 404,
                "error" => [
                    "message" => "The file could not be found"
                ]
            ];
        }
    }

    public function renameAudio($newName)
    {
        $this->dbChecker->executeQuery("UPDATE audio_files SET file_name = '$newName' WHERE id = " . $this->audioId . " AND user_id = " . $this->dbChecker->userId);

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

    public function moveAudio($newFolderId, $convert = FALSE)
    {
        $moveAudioOp = new MoveAudioOperation($this->audioId, $newFolderId, $convert, $this->dbChecker);
        return $moveAudioOp->compute();
    }

    public function shareAudio($recipientEmail)
    {
        $shareeIdQuery = $this->dbChecker->executeQuery("SELECT id FROM firstborumdatabase.users WHERE email = '$recipientEmail' LIMIT 1");

        if (mysqli_num_rows($shareeIdQuery) == 1) {
            $shareeId = mysqli_fetch_array($shareeIdQuery)[0];
            $this->dbChecker->executeQuery("INSERT INTO file_sharing (sharer_id, file_id, receiver_id) VALUES (" . $this->dbChecker->userId . ", " . $this->audioId . ", $shareeId)");
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
