<?php 

namespace Flytrap\DBHandlers;

use Flytrap\Computable;
use Flytrap\EndpointResponse;
use Flytrap\FilterType;

use Flytrap\Security\NumberAlphaIdConverter;

class FilterAudioOperation implements Computable {
    protected $folderId;
    protected $filterType;
    protected $dbChecker;

    public function __construct($dbChecker, $folderId, FilterType $filterType) {
        $this->dbChecker = $dbChecker;
        $this->folderId = $folderId;
        $this->filterType = $filterType;
    }

    public function compute()
    {
        $folderIdQuery = $this->folderId;
        switch ($this->filterType) {
            case FilterType::ALL:
                return $this->outputEndpointResponseFromQuery("
                SELECT id, file_name, audio_files.id AS afid, audio_files.time_created FROM audio_files 
                WHERE user_id = " . $this->dbChecker->userId . " AND folder_id $folderIdQuery 
                UNION SELECT file_sharing.id, audio_files.file_name, audio_files.id 
                AS afid, audio_files.time_created 
                FROM file_sharing JOIN audio_files ON file_sharing.file_id = audio_files.id 
                WHERE receiver_id = " . $this->dbChecker->userId . " AND file_sharing.folder_id = $this->folderId
                ");
            case FilterType::OWNED:
                return $this->outputEndpointResponseFromQuery("
                SELECT id AS afid, file_name, time_created, user_id 
                FROM audio_files 
                WHERE user_id = " . $this->dbChecker->userId . " AND folder_id $folderIdQuery");
            case FilterType::SHARED:
                return $this->outputEndpointResponseFromQuery("SELECT file_sharing.id, audio_files.file_name, audio_files.id AS afid, audio_files.time_created FROM file_sharing JOIN audio_files ON audio_files.id = file_sharing.file_id WHERE file_sharing.receiver_id = " . $this->dbChecker->userId . " AND file_sharing.folder_id $folderIdQuery");
            default:
                return [
                    "statusCode" => 404,
                    "error" => [
                        "message" => "A server error occurred"
                    ]
                ];
        }
    }

    private function outputEndpointResponseFromQuery($query) {
        $data = $this->getFilteredDataAndAlphaId($query);
        return EndpointResponse::outputSuccessWithData($data);
    }

    private function getFilteredDataAndAlphaId($q)
    {
        $numAlphaIdConverter = new NumberAlphaIdConverter(10);
        $r = $this->dbChecker->executeQuery($q);
        $encoded = [];
        while ($row = mysqli_fetch_array($r, MYSQLI_BOTH)) {
            $row['afid'] = $numAlphaIdConverter->convertNumericIdToAlphaId($row['afid']);
            array_push($encoded, $row);
        }

        return $encoded;
    }
}


?>