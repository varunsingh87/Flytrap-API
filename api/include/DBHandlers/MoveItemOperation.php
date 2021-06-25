<?php

namespace Flytrap\DBHandlers;

use Flytrap\Computable;
use Flytrap\EndpointResponse;

class MoveItemOperation implements Computable {
    protected int $fileId;
    protected string $newFolderId;
    protected UserChecker $dbHandler;
    protected string $itemType;
    protected string $tableName;
    protected string $columnName;

    public function __construct($fileId, $newFolderId, $dbHandler, $itemType) {
        $this->fileId = $fileId;
        $this->newFolderId = $newFolderId;
        $this->dbHandler = $dbHandler;
        $this->itemType = $itemType;
        $this->tableName = $itemType === 'folder' ? 'folders' : 'audio_files';
        $this->columnName = $this->tableName == 'folders' ? 'parent_id' : 'folder_id';
    }

    public function compute() {
        $audio = $this->selectAudioFile($this->fileId);
        $userId = mysqli_fetch_array($audio)[0];

        if ($this->checkAudioFileExists($audio)) {
            if ($this->checkUserOwnsAudioFile($userId)) {
                $locUpdate = $this->updateItemLocation();
                
                if (gettype($locUpdate) == 'boolean') {
                    return EndpointResponse::outputSuccessWithoutData();
                } else {
                    return EndpointResponse::outputGenericError('', $locUpdate, 'The query did not affect any rows');
                }
            } else {
                return EndpointResponse::outputSpecificErrorMessage(401, 'The ' . $this->itemType . ' was not moved because you do not own it');
            }
        } else {
            return EndpointResponse::outputSpecificErrorMessage(404, 'That is not a valid id');
        }

        if ($this->dbHandler->lastQueryWasSuccessful()) {
            return EndpointResponse::outputSuccessWithoutData();
        } else {
            return EndpointResponse::outputGenericError(' and the ' . $this->itemType . ' was not moved');
        }
    }

    private function selectAudioFile() {
        return $this->dbHandler->executeQuery("SELECT user_id FROM " . $this->tableName . " WHERE id = " . $this->fileId);
    }

    private function checkAudioFileExists($result) {
        return mysqli_num_rows($result) == 1;
    }

    private function checkUserOwnsAudioFile($audioFileOwner) {
        return $this->dbHandler->userId == $audioFileOwner;
    }

    private function updateItemLocation() {
        $query = "UPDATE " . $this->tableName . " SET " . $this->columnName . " = ? WHERE id = ? LIMIT 1";
        $preparedStatement = $this->dbHandler->getConnection()->prepare($query);
        
        if (gettype($preparedStatement) == 'boolean') return "Query: $query\n" . "Error: " . mysqli_error($this->dbHandler->getConnection());

        $preparedStatement->bind_param('si', $this->newFolderId, $this->fileId);
        
        $success = $preparedStatement->execute();
        
        return $success ? $success : $query;
    }
}

?>