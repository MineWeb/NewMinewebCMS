<?php
declare(strict_types=1);

$root = dirname(__DIR__);

require $root . '/templates/Install/requirements.php';

if (PHP_SAPI === 'cli-server') {
    $_SERVER['PHP_SELF'] = '/' . basename(__FILE__);
    $url = parse_url(urldecode($_SERVER['REQUEST_URI']));
    $file = __DIR__ . $url['path'];
    if (!str_contains($url['path'], '..') && str_contains($url['path'], '.') && is_file($file)) {
        return false;
    }
}

require $root . '/config/paths.php';
require $root . '/vendor/autoload.php';

use App\Application;
use Cake\Http\Server;

$server = new Server(new Application($root . '/config'));
$server->emit($server->run());
