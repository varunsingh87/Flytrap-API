<?php


namespace Flytrap\DBHandlers;

use Flytrap\EndpointResponse;
use Flytrap\DBHandlers\UserChecker;

use Flytrap\Security\NumberAlphaIdConverter;

class FolderHandler
{
    protected UserChecker $dbChecker;
    protected NumberAlphaIdConverter $toAlphaId;
    protected $folderAlphaId;

    public function __construct($userApiKey)
    {
        $this->dbChecker = new UserChecker($userApiKey);
        $this->toAlphaId = new NumberAlphaIdConverter(10);
    }

    public function setFolderAlphaId($folderAlphaId = 0)
    {
        $this->folderAlphaId = $folderAlphaId;
        $this->checkUserOwnsFolder();
    }

    private function checkUserOwnsFolder()
    {
        if (isset($this->folderAlphaId)) {
            $result = $this->dbChecker->executeQuery(
                "SELECT user_id FROM folders WHERE alpha_id = " . $this->folderAlphaId
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

    public function getFolderInfo()
    {
        $query = "SELECT id, folder_name, parent_id, time_created FROM folders WHERE id = " . $this->folderAlphaId;

        $folderInfo = $this->dbChecker->executeQuery($query);

        if (mysqli_num_rows($folderInfo) != 1) {
            return EndpointResponse::outputGenericError();
        }

        $folderInfo = mysqli_fetch_assoc($folderInfo);

        return EndpointResponse::outputSuccessWithData($folderInfo);
    }

    public function getFolderAudioFiles()
    {
        $query = "SELECT * FROM audio_files 
        JOIN folders ON folders.id = audio_files.folder_id 
        WHERE folders.alpha_id = " . $this->folderAlphaId;

        $audioFiles = $this->dbChecker->executeQuery($query);

        if (!!!$audioFiles)
            return EndpointResponse::outputGenericError();

        $audioFiles = mysqli_fetch_all($audioFiles, MYSQLI_ASSOC);

        return EndpointResponse::outputSuccessWithData($audioFiles);
    }

    public function getFolderSubdirectories()
    {
        $query = "SELECT folders.id AS id, folders.alpha_id AS alpha_id, 
        folders.folder_name AS folder_name, folders.time_created AS time_created FROM folders 
        JOIN folders AS parent_folders ON folders.parent_id = parent_folders.id 
        WHERE parent_folders.alpha_id = " . $this->folderAlphaId;

        $subdirs = $this->dbChecker->executeQuery($query);

        if (!!!$subdirs) {
            return EndpointResponse::outputGenericError();
        }

        $subdirs = mysqli_fetch_all($subdirs, MYSQLI_ASSOC);

        return EndpointResponse::outputSuccessWithData($subdirs);
    }
}

?>