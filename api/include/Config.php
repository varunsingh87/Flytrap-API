<?php 

namespace VarunS\Flytrap\DBHandlers;

class Config {
    function __construct($user, $pass, $host, $name) {
        $this->dbUsername = $user;
        $this->dbPassword = $pass;
        $this->dbHost = $host;
        $this->dbName = $name;
    }

    static function createConfigFromEnv() {
        return new Config($_ENV["DB_USERNAME"], $_ENV["DB_PASSWORD"], $_ENV["DB_HOST"], $_ENV["DB_NAME"]);
    }
}

?>