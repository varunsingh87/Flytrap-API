<?php 

namespace VarunS\Flytrap\DBHandlers;

use VarunS\Flytrap\EndpointResponse;

class FolderHandler {
    protected UserChecker $dbChecker;
    protected $folderId;

    public function __construct($userApiKey)
    {
        $this->dbChecker = new UserChecker($userApiKey);
    }

    public function setFolderId($folderId) {
        $this->folderId = $folderId;
    }

    public function getFolder() {
        $query = "SELECT * FROM folders WHERE user_id = " . $this->dbChecker->userId . " AND parent_id ";
        
        if (is_numeric($query)) 
            $query .= "= " . $this->folderId;
        else
            $query .= "IS NULL";

        $audioFiles = $this->dbChecker->executeQuery($query);

        if (!((bool) $audioFiles)) {
            return [
                "statusCode" => 404,
                "error" => [
                    "message" => "You do not have access to that folder or it does not exist"
                ]
            ];
        }

        $audioFiles = mysqli_fetch_all($audioFiles, MYSQLI_ASSOC);

        $response = new EndpointResponse($audioFiles);
        return $response;
    }
}

?>