<?php


namespace Flytrap\DBHandlers;

use Flytrap\EndpointResponse;
use Flytrap\DBHandlers\UserChecker;

use Flytrap\Security\NumberAlphaIdConverter;

class FolderHandler
{
    protected UserChecker $dbChecker;
    protected $folderAlphaId;
    protected $generator;

    public function __construct($userApiKey)
    {
        $this->dbChecker = new UserChecker($userApiKey);
        $this->generator = new NumberAlphaIdConverter(10);
    }

    public function setFolderAlphaId($folderAlphaId = 0)
    {
        $this->folderAlphaId = empty($folderAlphaId) ? 0 : $folderAlphaId;
        $this->checkUserOwnsFolder();
    }

    private function checkUserOwnsFolder()
    {
        if (isset($this->folderAlphaId)) {
            $result = $this->dbChecker->executeQuery(
                "SELECT user_id FROM folders WHERE alpha_id = '" . $this->folderAlphaId . "'"
            );
        }
        else
            // Prevent future errors and return successful validation because all users have a root folder
            return true;

        $exists = mysqli_num_rows($result) == 1;

        // Only give access to folder if the user owns it 
        // TODO: Give access if shared with user
        if ($exists) {
            $userOwnsFolder = mysqli_fetch_array($result)[0];
            if ($userOwnsFolder != $this->dbChecker->userId) {
                return EndpointResponse::outputSpecificErrorMessage(401, 'You do not have permission to access that folder');
            }
            else {
                return true;
            }
        }
        // Speed up request by skipping instance method queries if the folder does not exist 
        else {
            return EndpointResponse::outputSpecificErrorMessage(404, 'That folder does not exist');
        }

    }

    private function isFolderIdZero()
    {
        return $this->folderAlphaId == 0;
    }

    private function isFolderIdAlphanumeric()
    {
        return ctype_alnum($this->folderAlphaId);
    }

    public function getFolderInfo()
    {
        if (!$this->isFolderIdAlphanumeric())
            return EndpointResponse::outputSuccessWithoutData();

        $query = "SELECT id, folder_name, parent_id, time_created 
            FROM folders WHERE alpha_id = '" . $this->folderAlphaId . "' LIMIT 1";

        $folderInfo = $this->dbChecker->executeQuery($query);

        $returnedRows = mysqli_num_rows($folderInfo);

        if ($returnedRows == 1) {
            $folderInfo = mysqli_fetch_assoc($folderInfo);
            return EndpointResponse::outputSuccessWithData($folderInfo);
        }
        else if ($returnedRows == 0) {
            return EndpointResponse::outputSpecificErrorMessage("404", "That folder does not exist");
        }
        else {
            return EndpointResponse::outputGenericError();
        }
    }

    public function getFolderAudioFiles()
    {
        $query = "";

        if ($this->isFolderIdZero()) {
            $query = "SELECT * FROM audio_files WHERE folder_id = 0 AND user_id = " . $this->dbChecker->userId;
        }
        else if ($this->isFolderIdAlphanumeric()) {
            $query = "SELECT audio_files.id AS id, audio_files.alpha_id AS alpha_id, 
            audio_files.file_name AS file_name, audio_files.time_created AS time_created 
            FROM audio_files JOIN folders ON folders.id = audio_files.folder_id 
            WHERE folders.alpha_id = '" . $this->folderAlphaId . "' AND audio_files.user_id = " . $this->dbChecker->userId;
        }

        $audioFiles = $this->dbChecker->executeQuery($query);

        if (!!!$audioFiles)
            return EndpointResponse::outputGenericError();

        $audioFiles = mysqli_fetch_all($audioFiles, MYSQLI_ASSOC);

        return EndpointResponse::outputSuccessWithData($audioFiles);
    }

    public function getFolderSubdirectories()
    {
        $query = "";
        if ($this->isFolderIdZero()) {
            $query = "SELECT id, alpha_id, folder_name, time_created FROM folders 
            WHERE parent_id = 0 AND user_id = " . $this->dbChecker->userId;
        }
        else if ($this->isFolderIdAlphanumeric()) {
            $query = "SELECT folders.id AS id, folders.alpha_id AS alpha_id, 
            folders.folder_name AS folder_name, folders.time_created AS time_created 
            FROM folders JOIN folders AS parent_folders ON folders.parent_id = parent_folders.id 
            WHERE parent_folders.alpha_id = '" . $this->folderAlphaId . "' AND parent_folders.user_id = " . $this->dbChecker->userId;
        }

        $subdirs = $this->dbChecker->executeQuery($query);

        if (!!!$subdirs) {
            return EndpointResponse::outputGenericError();
        }

        $subdirs = mysqli_fetch_all($subdirs, MYSQLI_ASSOC);

        return EndpointResponse::outputSuccessWithData($subdirs);
    }

    public function createNewFolder($newFolderName)
    {
        $parentFolderId = 0;

        if ($this->isFolderIdAlphanumeric()) {
            $parentId = $this->folderAlphaId;
            $parentFolderIdQuery = "SELECT id FROM folders WHERE alpha_id = '$parentId' AND user_id = " . $this->dbChecker->userId;
            $parentFolderId = $this->dbChecker->executeQuery($parentFolderIdQuery);
    
            if (!!!$parentFolderId)
                return EndpointResponse::outputGenericError();
    
            if (mysqli_num_rows($parentFolderId) < 1)
                return EndpointResponse::outputSpecificErrorMessage("404", "That folder does not exist in your Flytrap account");    
            
            $parentFolderId = mysqli_fetch_array($parentFolderId)[0];
        }
        
        $alphaId = $this->generator->generateId();

        $this->dbChecker->executeQuery("INSERT INTO folders (alpha_id, parent_id, folder_name, user_id) VALUES ('$alphaId', $parentFolderId, '$newFolderName', " . $this->dbChecker->userId . ")");

        if ($this->dbChecker->lastQueryWasSuccessful()) {
            $folder = $this->dbChecker->executeQuery("SELECT * FROM folders WHERE alpha_id = '$alphaId' ORDER BY time_created DESC LIMIT 1");

            if (mysqli_num_rows($folder) == 1) {
                $folder = mysqli_fetch_assoc($folder);
                return EndpointResponse::outputSuccessWithData($folder);
            }
        }

        return EndpointResponse::outputGenericError(" and the folder was not created.");
    }

    /**
     * Deletes a folder using either the alpha id or numeric id
     */
    public function deleteFolder() {
        if (is_numeric($this->folderAlphaId))
            $this->dbChecker->executeQuery("DELETE FROM folders WHERE id = " . $this->folderAlphaId . " AND user_id = " . $this->dbChecker->userId);
        else if ($this->isFolderIdAlphanumeric())
            $this->dbChecker->executeQuery("DELETE FROM folders WHERE alpha_id = '" . $this->folderAlphaId . "' AND user_id = " . $this->dbChecker->userId);
        else {
            return EndpointResponse::outputSpecificErrorMessage(
                "400", 
                "The folder was not deleted. This is likely not a problem with our servers."
            );
        }

        if ($this->dbChecker->lastQueryWasSuccessful()) 
            return EndpointResponse::outputSuccessWithoutData();
        else if ($this->dbChecker->lastQueryAffectedNoRows()) {
            return EndpointResponse::outputSpecificErrorMessage(
                "404", 
                "The folder was not found for this account"
            );
        }

        return EndpointResponse::outputGenericError();
    }

    public function shareFolder($recipientEmail) {
        $q = "SELECT id FROM firstborumdatabase.users WHERE email = \"$recipientEmail\" LIMIT 1";
        $result = $this->dbChecker->executeQuery($q);
        $shareId = mysqli_fetch_array($result)[0];

        $userId = $this->userId;
        $folderId = $this->folderAlphaId;

        if ($result->num_rows == 1) {
            $q = "INSERT INTO folder_sharing (sharer_id, folder_id, receiver_id) VALUES ($userId, '$folderId', $shareId)";
            $this->dbChecker->executeQuery($q);

            if ($this->dbChecker->lastQueryWasSuccessful())
                return EndpointResponse::outputSuccessWithoutData();
            else
                return EndpointResponse::outputSpecificErrorMessage(500, "A server error occurred", $q);
        } else {
            return EndpointResponse::outputSpecificErrorMessage(404, "A Borum account for that email does not exist");
        }
    }
}
