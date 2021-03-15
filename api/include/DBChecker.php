<?php

namespace VarunS\Flytrap\DBHandlers;

use VarunS\PHPSleep\DBHandlers\DBHandler;

class DBChecker extends DBHandler
{
    function __construct()
    {
        parent::__construct($_ENV["DB_USERNAME"], $_ENV["DB_PASSWORD"], $_ENV["DB_HOST"], $_ENV["DB_NAME"]);
    }
}
