<?php 

namespace VarunS\Flytrap\DBHandlers;

class FolderHandler {
    protected UserChecker $dbChecker;
    protected $folderId;

    public function __construct($userApiKey, $folderId)
    {
        $this->dbChecker = new UserChecker($userApiKey);
        $this->folderId = $folderId;
    }

    
}

?>