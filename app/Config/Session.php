<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Session\Handlers\BaseHandler;
use CodeIgniter\Session\Handlers\DatabaseHandler;

class Session extends BaseConfig
{
    public string $driver = DatabaseHandler::class;

    public string $cookieName = 'ci_session';

    public int $expiration = 7200;

    // IMPORTANT: table name
    public string $savePath = 'ci_sessions';

    public bool $matchIP = false;

    public int $timeToUpdate = 300;

    public bool $regenerateDestroy = false;

    public ?string $DBGroup = null;

    public bool $cookieSecure = true;

    public string $sameSite = 'None';

    public int $lockRetryInterval = 100_000;

    public int $lockMaxRetries = 300;
}
