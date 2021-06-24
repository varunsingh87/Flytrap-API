<?php

namespace Flytrap\DBHandlers;

use Flytrap\Security\NumberAlphaIdConverter;
use Flytrap\Computable;
use Flytrap\EndpointResponse;

class MoveAudioOperation implements Computable {
    protected int $fileId;
    protected string $newFolderId;
    protected int $convert;
    protected $alphaIdConversion;
    protected UserChecker $dbHandler;

    public function __construct($fileId, $newFolderId, $convert = 0, $dbHandler) {
        $this->fileId = $fileId;
        $this->newFolderId = $newFolderId;
        $this->convert = $convert;
        $this->alphaIdConversion = new NumberAlphaIdConverter(10);
        $this->dbHandler = $dbHandler;
    }

    public function compute() {
        $audio = $this->selectAudioFile($this->fileId);
        $userId = mysqli_fetch_array($audio)[0];

        if ($this->checkAudioFileExists($audio)) {
            if ($this->checkUserOwnsAudioFile($userId)) {
                $locUpdate = $this->updateAudioFileLocation();
                
                if (gettype($locUpdate) == 'bool') {
                    return EndpointResponse::outputSuccessWithoutData();
                } else {
                    return EndpointResponse::outputGenericError('', $locUpdate, 'The query did not affect any rows');
                }
            } else {
                return EndpointResponse::outputSpecificErrorMessage(401, 'The audio was not moved because you do not own that audio file');
            }
        } else {
            return EndpointResponse::outputSpecificErrorMessage(404, 'That is not a valid id');
        }

        if ($this->dbHandler->lastQueryWasSuccessful()) {
            return EndpointResponse::outputSuccessWithoutData();
        } else {
            return EndpointResponse::outputGenericError(' and the audio was not moved');
        }
    }

    private function selectAudioFile() {
        return $this->dbHandler->executeQuery("SELECT user_id FROM audio_files WHERE id = " . $this->fileId);
    }

    private function checkAudioFileExists($result) {
        return mysqli_num_rows($result) == 1;
    }

    private function checkUserOwnsAudioFile($audioFileOwner) {
        return $this->dbHandler->userId == $audioFileOwner;
    }

    private function updateAudioFileLocation() {
        $query = "UPDATE audio_files SET folder_id = ? WHERE id = ? LIMIT 1";
        $preparedStatement = $this->dbHandler->getConnection()->prepare($query);
        $preparedStatement->bind_param('si', $this->newFolderId, $this->fileId);
        $preparedStatement->execute();
        
        if ($this->dbHandler->lastQueryWasSuccessful()) return true;
        else return $query;
    }
}

?>