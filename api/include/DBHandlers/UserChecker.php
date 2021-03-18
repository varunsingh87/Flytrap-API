<?php

namespace Flytrap\DBHandlers;

use VarunS\PHPSleep\DBHandlers\UserKnownHandler;

class UserChecker extends UserKnownHandler
{
    function __construct($userApiKey)
    {
        parent::__construct($userApiKey, $_ENV["DB_USERNAME"], $_ENV["DB_PASSWORD"], $_ENV["DB_HOST"], $_ENV["DB_NAME"]);
    }
}
