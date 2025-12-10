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
use Detection\MobileDetect;

require CAKE . 'functions.php';

try {
    Configure::config('default', new PhpConfig());
    Configure::load('app', 'default', false);
} catch (Exception $e) {
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

date_default_timezone_set((string)Configure::read('App.defaultTimezone'));
mb_internal_encoding((string)Configure::read('App.encoding'));
ini_set('intl.default_locale', (string)Configure::read('App.defaultLocale'));

(new ErrorTrap((array)Configure::read('Error')))->register();
(new ExceptionTrap((array)Configure::read('Error')))->register();

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

Cache::setConfig((array)Configure::consume('Cache'));
TransportFactory::setConfig((array)Configure::consume('EmailTransport'));
Mailer::setConfig((array)Configure::consume('Email'));
Log::setConfig((array)Configure::consume('Log'));
Security::setSalt((string)Configure::consume('Security.salt'));

ServerRequest::addDetector('mobile', function () {
    $detector = new MobileDetect();
    return $detector->isMobile();
});
ServerRequest::addDetector('tablet', function () {
    $detector = new MobileDetect();
    return $detector->isTablet();
});

TypeFactory::map('time', StringType::class);

require_once ROOT . DS . 'config' . DS . 'function.php';

$configDir = ROOT . DS . 'config' . DS;
$dbConfigFile = $configDir . 'databases.json';

$dbConfigured = false;
if (is_readable($dbConfigFile)) {
    $dbData = json_decode((string)file_get_contents($dbConfigFile));
    if ($dbData && isset($dbData->configured) && $dbData->configured === true) {
        if (!empty($dbData->driver) && !empty($dbData->host) && !empty($dbData->username) && !empty($dbData->database)) {
            $dbConfigured = true;
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
                'quoteIdentifiers' => true
            ]);
        }
    }
}
Configure::write('Install.dbConfigured', $dbConfigured);

$installLockFile = $configDir . 'install.lock';
$installed = file_exists($installLockFile);
Configure::write('Install.installed', $installed);
