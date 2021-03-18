<?php 

namespace Flytrap\DBHandlers;

use Flytrap\EndpointResponse;

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
        if ($this->folderId == NULL) {
            return [
                "statusCode" => 500,
                "error" => [
                    "message" => "A server error occurred",
                    "developer" => "The folder id was not specified by the API endpoint"
                ]
            ];
        }

        $audioFiles = $this->dbChecker->executeQuery("SELECT * FROM folders WHERE user_id = " . $this->dbChecker->userId . " AND id = " . $this->folderId);

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