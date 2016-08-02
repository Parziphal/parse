<?php

use Parse\ParseClient;

$autoloader = require __DIR__ . '/../vendor/autoload.php';

$autoloader->addPsr4('Parziphal\\Parse\\Test\\', __DIR__);
$autoloader->addPsr4('Parziphal\\Parse\\', __DIR__ . '/../src', true);

$appId     = getenv('parseAppId');
$masterKey = getenv('parseMasterKey');
$serverUrl = getenv('parseServerUrl');
$mountPath = getenv('parseMountPath');

ParseClient::initialize($appId, null, $masterKey);
ParseClient::setServerURL($serverUrl, $mountPath);
