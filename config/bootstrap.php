<?php
declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'paths.php';
require CORE_PATH . 'config' . DS . 'bootstrap.php';

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Database\Type\StringType;
use Cake\Database\TypeFactory;
use Cake\Datasource\ConnectionManager;
use Cake\Error\ErrorTrap;
use Cake\Error\ExceptionTrap;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\Mailer\TransportFactory;
use Cake\Routing\Router;
use Cake\Utility\Security;
use Migrations\Migrations;

require CAKE . 'functions.php';

try {
    Configure::config('default', new PhpConfig());
    Configure::load('app', 'default', false);
} catch (\Exception $e) {
    exit($e->getMessage() . "\n");
}

if (file_exists(CONFIG . 'app_local.php')) {
    Configure::load('app_local', 'default');
}

if (Configure::read('debug')) {
    Configure::write('Cache._cake_model_.duration', '+2 minutes');
    Configure::write('Cache._cake_core_.duration', '+2 minutes');
    Configure::write('Cache._cake_routes_.duration', '+2 seconds');
}

date_default_timezone_set(Configure::read('App.defaultTimezone'));
mb_internal_encoding(Configure::read('App.encoding'));
ini_set('intl.default_locale', Configure::read('App.defaultLocale'));

(new ErrorTrap(Configure::read('Error')))->register();
(new ExceptionTrap(Configure::read('Error')))->register();

if (PHP_SAPI === 'cli') {
    require CONFIG . 'bootstrap_cli.php';
}

$fullBaseUrl = Configure::read('App.fullBaseUrl');
if (!$fullBaseUrl) {
    $trustProxy = false;
    $s = null;

    if (env('HTTPS') || ($trustProxy && env('HTTP_X_FORWARDED_PROTO') === 'https')) {
        $s = 's';
    }

    $httpHost = env('HTTP_HOST');
    if ($httpHost) {
        $fullBaseUrl = 'http' . $s . '://' . $httpHost;
    }
    unset($httpHost, $s);
}
if ($fullBaseUrl) {
    Router::fullBaseUrl($fullBaseUrl);
}
unset($fullBaseUrl);

Cache::setConfig(Configure::consume('Cache'));
TransportFactory::setConfig(Configure::consume('EmailTransport'));
Mailer::setConfig(Configure::consume('Email'));
Log::setConfig(Configure::consume('Log'));
Security::setSalt(Configure::consume('Security.salt'));

ServerRequest::addDetector('mobile', function ($request) {
    $detector = new \Detection\MobileDetect();
    return $detector->isMobile();
});
ServerRequest::addDetector('tablet', function ($request) {
    $detector = new \Detection\MobileDetect();
    return $detector->isTablet();
});

TypeFactory::map('time', StringType::class);

require_once ROOT . DS . 'config' . DS . 'function.php';

$configDir = ROOT . DS . 'config' . DS;
$dbConfigFile = $configDir . 'databases.json';

if (!is_readable($dbConfigFile)) {
    exit("Missing or unreadable databases.json\n");
}

$dbData = json_decode((string)file_get_contents($dbConfigFile));

if (!$dbData || !isset($dbData->driver, $dbData->host, $dbData->username, $dbData->database)) {
    exit("Invalid databases.json configuration\n");
}

ConnectionManager::setConfig('default', [
    'className' => 'Cake\Database\Connection',
    'driver' => 'Cake\Database\Driver\\' . $dbData->driver,
    'persistent' => false,
    'host' => $dbData->host,
    'username' => $dbData->username,
    'password' => $dbData->password ?? '',
    'database' => $dbData->database,
    'encoding' => 'utf8mb4',
    'timezone' => 'UTC',
    'cacheMetadata' => true,
    'quoteIdentifiers' => true,
]);

$installFlagFile = $configDir . 'install.txt';

if (!file_exists($installFlagFile)) {
    $migrations = new Migrations();

    try {
        $migrations->migrate();
        $migrations->seed();
    } catch (\Exception $e) {
        echo $e->getMessage() . "\n";
        exit("Unable to install database (create config/install.txt to skip auto install)\n");
    }

    $data = 'CREATED AT ' . date('H:i:s d/m/Y') . "\n";
    file_put_contents($installFlagFile, $data);
}
