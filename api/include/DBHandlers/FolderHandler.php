<?php 

namespace Flytrap\DBHandlers;

use Flytrap\EndpointResponse;
use Flytrap\DBHandlers\UserChecker;

class FolderHandler {
    protected UserChecker $dbChecker;
    protected $folderId;

    public function __construct($userApiKey)
    {
        $this->dbChecker = new UserChecker($userApiKey);
        $this->checkUserOwnsFolder();
    }
    
    public function setFolderId($folderId) {
        $this->folderId = $folderId;
    }
    
    private function checkUserOwnsFolder() {
        $result = $this->dbChecker->executeQuery("SELECT user_id FROM folders WHERE id = " . $this->folderId);
        $exists = mysqli_num_rows($result) == 1;

        // Only give access to folder if the user owns it 
        // TODO: Give access if shared with user
        if ($exists) {
            $userOwnsFolder = mysqli_fetch_array($result)[0];
            if ($userOwnsFolder != $this->dbChecker->userId) {
                return EndpointResponse::outputSpecificErrorMessage('403', 'You do not have permission to access that folder');
            }
        }
        // Speed up request by skipping instance method queries if the folder does not exist 
        else {
            return EndpointResponse::outputSpecificErrorMessage('404', 'That folder does not exist');
        }

    }

    public function getFolderAudioFiles() {
        $query = "SELECT * FROM audio_files WHERE folder_id ";
        
        if (is_numeric($query)) 
            $query .= "= " . $this->folderId;
        else
            $query .= "IS NULL";

        $audioFiles = $this->dbChecker->executeQuery($query);
        
        if (!!!$audioFiles) return EndpointResponse::outputGenericError();
        
        $audioFiles = mysqli_fetch_all($audioFiles, MYSQLI_ASSOC);

        return EndpointResponse::outputSuccessWithData($audioFiles);
    }
}

?>