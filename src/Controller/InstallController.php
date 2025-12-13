<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\InstallState;
use App\Service\UserAuthService;
use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Log\Log;
use Migrations\Migrations;
use PDO;
use PDOException;
use Throwable;

/**
 * @property \App\Controller\Component\UtilComponent $Util
 */
class InstallController extends BaseController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Util');
        $this->viewBuilder()->setLayout('install');
    }

    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        if (InstallState::isInstalled()) {
            $this->setResponse($this->redirect(['_name' => 'home']));
            $event->stopPropagation();
            return;
        }

        $path = (string)$this->request->getUri()->getPath();

        if (strncmp($path, '/install', 8) !== 0 && $path !== '/') {
            $this->setResponse($this->redirect(['_name' => 'install_index']));
            $event->stopPropagation();
        }
    }

    public function index(): Response
    {
        if (!InstallState::isDatabaseConfigured()) {
            return $this->redirect(['_name' => 'install_database']);
        }

        return $this->redirect(['_name' => 'install_user']);
    }

    public function database(): Response
    {
        if ($this->request->is('post')) {
            return $this->handleDatabasePost();
        }

        $titleKey = 'INSTALL__DATABASE_CONFIG';
        $title = __($titleKey);
        if ($title === $titleKey) {
            $title = 'Configuration de la base de donnees';
        }
        $this->set('title_for_layout', $title);

        $dbConfigured = InstallState::isDatabaseConfigured();
        $needDisplayDatabase = !$dbConfigured;

        $compatible = [];
        $help = [];

        $compatible['chmod'] =
            is_writable(ROOT . DIRECTORY_SEPARATOR . 'config') &&
            is_writable(ROOT . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Data') &&
            is_writable(ROOT . DIRECTORY_SEPARATOR . 'plugins') &&
            is_writable(ROOT . DIRECTORY_SEPARATOR . 'tmp') &&
            is_writable(ROOT . DIRECTORY_SEPARATOR . 'webroot' . DIRECTORY_SEPARATOR . 'js');

        if (!$compatible['chmod']) {
            $help['chmod'] = '';

            if (!is_writable(ROOT . DIRECTORY_SEPARATOR . 'config')) {
                $help['chmod'] .= 'Le dossier /config ne peut pas etre ecrit.<br /><br />';
            }

            if (!is_writable(ROOT . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Data')) {
                $help['chmod'] .= 'Le dossier /src/Data ne peut pas etre ecrit.<br /><br />';
            }

            if (!is_writable(ROOT . DIRECTORY_SEPARATOR . 'plugins')) {
                $help['chmod'] .= 'Le dossier /plugins ne peut pas etre ecrit.<br /><br />';
            }

            if (!file_exists(ROOT . DIRECTORY_SEPARATOR . 'tmp')) {
                $help['chmod'] .= 'Le dossier /tmp n existe pas.<br /><br />';
            } elseif (!is_writable(ROOT . DIRECTORY_SEPARATOR . 'tmp')) {
                $help['chmod'] .= 'Le dossier /tmp ne peut pas etre ecrit.<br /><br />';
            }

            if (!is_writable(ROOT . DIRECTORY_SEPARATOR . 'webroot' . DIRECTORY_SEPARATOR . 'js')) {
                $help['chmod'] .= 'Le dossier /webroot/js ne peut pas etre ecrit.<br /><br />';
            }
        }

        $compatible['pdo'] = in_array('pdo_mysql', get_loaded_extensions(), true);
        $compatible['curl'] = extension_loaded('curl');
        $compatible['gd2'] = function_exists('imagettftext');
        $compatible['openZip'] = function_exists('zip_open');
        $compatible['openSSL'] = extension_loaded('openssl');

        if (!$compatible['pdo']) {
            $help['pdo'] = 'L extension pdo_mysql n est pas activee.';
        }

        if (!$compatible['curl']) {
            $help['curl'] = 'L extension curl n est pas activee.';
        }

        if (!$compatible['gd2']) {
            $help['gd2'] = 'L extension GD2 n est pas activee.';
        }

        if (!$compatible['openZip']) {
            $help['openZip'] = 'L extension zip n est pas activee.';
        }

        if (!$compatible['openSSL']) {
            $help['openSSL'] = 'L extension OpenSSL n est pas activee.';
        }

        $compatible['rewriteUrl'] = true;

        $allowUrlFopen = false;
        if (function_exists('ini_get') && ini_get('allow_url_fopen') === '1') {
            $allowUrlFopen = true;
        } else {
            $context = stream_context_create(['http' => ['timeout' => 1]]);
            $probe = @file_get_contents('https://google.fr', false, $context);
            if ($probe !== false) {
                $allowUrlFopen = true;
            }
        }

        $compatible['allowGetURL'] = $allowUrlFopen;

        $needAffichCompatibility = in_array(false, $compatible, true);

        if (file_exists(ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'bypass_compatibility')) {
            $needAffichCompatibility = false;
        }

        if ($needAffichCompatibility) {
            $needDisplayDatabase = false;
        }

        $this->set(compact(
            'compatible',
            'help',
            'needAffichCompatibility',
            'needDisplayDatabase',
            'dbConfigured'
        ));

        $this->viewBuilder()
            ->setTemplatePath('Install')
            ->setTemplate('database');

        return $this->render();
    }

    public function install(): Response
    {
        $this->disableAutoRender();

        if (!$this->request->is('post')) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'status' => false,
                    'msg' => 'Methode invalide',
                ]));
        }

        if (!InstallState::isDatabaseConfigured()) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'status' => false,
                    'msg' => 'La base de donnees n est pas configuree',
                ]));
        }

        $this->reloadConnectionFromDatabasesJson();
        $this->runMigrations();

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'status' => true,
            ]));
    }

    private function handleDatabasePost(): Response
    {
        $this->disableAutoRender();

        $type = (int)$this->request->getData('type');
        $host = (string)$this->request->getData('host');
        $database = (string)$this->request->getData('database');
        $username = (string)$this->request->getData('login');
        $password = (string)$this->request->getData('password');

        if ($type === 0) {
            if ($host === '' || $database === '' || $username === '') {
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'status' => false,
                        'msg' => __('ERROR__FILL_ALL_FIELDS'),
                    ]));
            }

            $dsn = 'mysql:host=' . $host . ';dbname=' . $database . ';charset=utf8mb4';

            try {
                new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
            } catch (PDOException $e) {
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'status' => false,
                        'msg' => 'Erreur lors de la connexion MySQL: ' . $e->getMessage(),
                    ]));
            }

            $dbData = [
                'driver' => 'Mysql',
                'host' => $host,
                'username' => $username,
                'password' => $password,
                'database' => $database,
            ];

            if (!InstallState::markDatabaseConfigured($dbData)) {
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'status' => false,
                        'msg' => 'Impossible d ecrire le fichier databases.json',
                    ]));
            }

            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode(['status' => true]));
        }

        if ($type === 1) {
            if (!in_array('pdo_sqlite', get_loaded_extensions(), true)) {
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'status' => false,
                        'msg' => 'Vous devez avoir l extension pdo_sqlite',
                    ]));
            }

            $dbPath = ROOT . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'database.db';
            $dsn = 'sqlite:' . $dbPath;

            try {
                new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
            } catch (PDOException $e) {
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'status' => false,
                        'msg' => 'Erreur lors de la connexion SQLite: ' . $e->getMessage(),
                    ]));
            }

            $dbData = [
                'driver' => 'Sqlite',
                'host' => '',
                'username' => '',
                'password' => '',
                'database' => $dbPath,
            ];

            if (!InstallState::markDatabaseConfigured($dbData)) {
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'status' => false,
                        'msg' => 'Impossible d ecrire le fichier databases.json',
                    ]));
            }

            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode(['status' => true]));
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'status' => false,
                'msg' => 'Type de base de donnees invalide',
            ]));
    }

    private function reloadConnectionFromDatabasesJson(): void
    {
        $file = InstallState::databasesFile();
        $json = file_get_contents($file);
        if ($json === false) {
            return;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return;
        }

        $driver = $data['driver'] ?? 'Mysql';

        $config = [];

        if ($driver === 'Mysql') {
            $config = [
                'className' => Connection::class,
                'driver' => Mysql::class,
                'host' => $data['host'] ?? 'localhost',
                'username' => $data['username'] ?? '',
                'password' => $data['password'] ?? '',
                'database' => $data['database'] ?? '',
                'encoding' => 'utf8mb4',
                'timezone' => 'UTC',
                'persistent' => false,
            ];
        } elseif ($driver === 'Sqlite') {
            $config = [
                'className' => Connection::class,
                'driver' => Sqlite::class,
                'database' => $data['database'] ?? '',
            ];
        }

        if ($config !== []) {
            ConnectionManager::drop('default');
            ConnectionManager::setConfig('default', $config);
        }
    }

    private function runMigrations(): void
    {
        $migrations = new Migrations();

        try {
            $migrations->migrate();
            $migrations->seed([
                'seed' => 'ConfigurationSeed',
            ]);
        } catch (Throwable $e) {
            Log::error('Migration or seed failed: ' . $e->getMessage());
        }
    }

    public function user(): Response
    {
        if (!InstallState::isDatabaseConfigured()) {
            return $this->redirect(['_name' => 'install_database']);
        }

        $titleKey = 'INSTALL__ADMIN_CONFIG';
        $title = __($titleKey);
        if ($title === $titleKey) {
            $title = 'Creation du compte administrateur';
        }
        $this->set('title_for_layout', $title);

        if ($this->request->is('post')) {
            return $this->handleUserPost();
        }

        $this->viewBuilder()
            ->setTemplatePath('Install')
            ->setTemplate('user');

        return $this->render();
    }

    private function handleUserPost(): Response
    {
        $this->disableAutoRender();

        $data = $this->request->getData();

        $ip = method_exists($this->Util, 'getIP')
            ? (string)$this->Util->getIP()
            : $this->request->clientIp();

        if (empty($data['username']) || empty($data['password']) || empty($data['password_confirmation']) || empty($data['email'])) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'statut' => false,
                    'msg' => __('ERROR__FILL_ALL_FIELDS'),
                ]));
        }

        if ($data['password'] !== $data['password_confirmation']) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'statut' => false,
                    'msg' => __('USER__ERROR_PASSWORDS_NOT_SAME'),
                ]));
        }

        if (!filter_var((string)$data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'statut' => false,
                    'msg' => __('USER__ERROR_EMAIL_NOT_VALID'),
                ]));
        }

        $Users = $this->fetchTable('Users');
        $existingAdmin = $Users->find()->first();

        if ($existingAdmin) {
            InstallState::markInstalled();

            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'statut' => true,
                    'msg' => __('INSTALL__ADMIN_ALREADY_EXISTS'),
                ]));
        }

        $auth = new UserAuthService();

        $dataToSave = [
            'username' => (string)$data['username'],
            'email' => (string)$data['email'],
            'password' => (string)$data['password'],
            'rank' => 4,
        ];

        $userId = $auth->createUser($dataToSave, $ip);

        if ($userId <= 0) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'statut' => false,
                    'msg' => __('ERROR__INTERNAL_ERROR'),
                ]));
        }

        InstallState::markInstalled();

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'statut' => true,
                'msg' => __('USER__REGISTER_SUCCESS'),
                'redirect' => '/',
            ]));
    }
}
