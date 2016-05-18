<?php

use Parse\ParseClient;

$autoloader = require __DIR__ . '/../vendor/autoload.php';

$autoloader->addPsr4('Parziphal\\Parse\\Test\\', __DIR__);

$appId     = getenv('parseAppId');
$masterKey = getenv('parseMasterKey');
$serverUrl = getenv('parseServerUrl');

ParseClient::initialize($appId, null, $masterKey);
ParseClient::setServerURL($serverUrl);
