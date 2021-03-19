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
        if (isset($this->folderId))
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
                return EndpointResponse::outputSpecificErrorMessage('403', 'You do not have permission to access that folder');
            } else {
                return true;
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