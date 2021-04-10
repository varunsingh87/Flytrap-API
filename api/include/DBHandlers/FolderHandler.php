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
    protected $folderId;

    public function __construct($userApiKey)
    {
        $this->dbChecker = new UserChecker($userApiKey);
        $this->checkUserOwnsFolder();
        $this->toAlphaId = new NumberAlphaIdConverter(10);
    }

    public function setFolderAlphaId($folderAlphaId)
    {
        $this->folderAlphaId = $folderAlphaId;
        $this->folderId = $this->toAlphaId->convertAlphaIdToNumericId($folderAlphaId);
    }

    private function checkUserOwnsFolder()
    {
        if (isset($this->folderAlphaId))
            $result = $this->dbChecker->executeQuery(
                "SELECT user_id FROM folders WHERE id = " . $this->folderId
            );
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

    public function getFolderInfo() {
        $query = "SELECT id, folder_name, time_created FROM folders WHERE id = " . $this->folderId;
        
        $folderInfo = $this->dbChecker->executeQuery($query);

        if (mysqli_num_rows($folderInfo) != 1) {
            return EndpointResponse::outputGenericError();
        }

        $folderInfo = mysqli_fetch_assoc($folderInfo);

        return EndpointResponse::outputSuccessWithData($folderInfo);
    }

    public function getFolderAudioFiles()
    {
        $query = "SELECT * FROM audio_files WHERE folder_id = " . $this->folderId;

        $audioFiles = $this->dbChecker->executeQuery($query);

        if (!!!$audioFiles)
            return EndpointResponse::outputGenericError();

        $audioFileDataWithAlphaIds = [];
        while ($audioFile = mysqli_fetch_array($audioFiles, MYSQLI_ASSOC)) {
            // Add the alpha id to each array
            $audioFile['alphaId'] = $this->toAlphaId->convertNumericIdToAlphaId($audioFile['id']);

            // Push the combined array to new array for output simplicity
            array_push($audioFileDataWithAlphaIds, $audioFile);
        }

        return EndpointResponse::outputSuccessWithData($audioFileDataWithAlphaIds);
    }

    public function getFolderSubdirectories()
    {
        $query = "SELECT id, folder_name, time_created FROM folders WHERE parent_id = " . $this->folderId;

        $subdirs = $this->dbChecker->executeQuery($query);

        if (!!!$subdirs) {
            return EndpointResponse::outputGenericError();
        }

        $folderDataWithAlphaIds = [];
        while ($folder = mysqli_fetch_array($subdirs, MYSQLI_ASSOC)) {
            // Add the alpha id to each array
            $folder['alphaId'] = $this->toAlphaId->convertNumericIdToAlphaId($folder['id']);

            // Push the combined array to new array to merge outputs
            array_push($folderDataWithAlphaIds, $folder);
        }

        return EndpointResponse::outputSuccessWithData($folderDataWithAlphaIds);
    }
}

?>