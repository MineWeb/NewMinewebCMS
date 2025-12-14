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

        $path = $this->request->getUri()->getPath();

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

        $this->set('title_for_layout', __('INSTALL__DB_TITLE'));

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
                    'messages' => __('ERROR__BAD_REQUEST'),
                ]));
        }

        if (!InstallState::isDatabaseConfigured()) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'status' => false,
                    'messages' => __('INSTALL__DB_NOT_CONFIGURED'),
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
                        'messages' => __('ERROR__FILL_ALL_FIELDS'),
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
                        'messages' => __('INSTALL__DB_MYSQL_CONNECT_ERROR', ['message' => $e->getMessage()]),
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
                        'messages' => __('INSTALL__DB_WRITE_CONFIG_FAILED'),
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
                        'messages' => __('INSTALL__EXT_PDO_SQLITE_MISSING'),
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
                        'messages' => __('INSTALL__DB_SQLITE_CONNECT_ERROR', ['message' => $e->getMessage()]),
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
                        'messages' => __('INSTALL__DB_WRITE_CONFIG_FAILED'),
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
                'messages' => __('INSTALL__DB_INVALID_TYPE'),
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
            $migrations->seed();
        } catch (Throwable $e) {
            Log::error('Migration or seed failed: ' . $e->getMessage());
        }
    }

    public function user(): Response
    {
        if (!InstallState::isDatabaseConfigured()) {
            return $this->redirect(['_name' => 'install_database']);
        }

        $this->set('title_for_layout', __('INSTALL__ADMIN_TITLE'));

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
                    'status' => false,
                    'messages' => __('ERROR__FILL_ALL_FIELDS'),
                ]));
        }

        if ($data['password'] !== $data['password_confirmation']) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'status' => false,
                    'messages' => __('USER__ERROR_PASSWORDS_NOT_SAME'),
                ]));
        }

        if (!filter_var((string)$data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'status' => false,
                    'messages' => __('USER__ERROR_EMAIL_NOT_VALID'),
                ]));
        }

        $Users = $this->fetchTable('Users');
        $existingAdmin = $Users->find()->first();

        if ($existingAdmin) {
            InstallState::markInstalled();

            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'status' => true,
                    'messages' => __('INSTALL__ADMIN_ALREADY_EXISTS'),
                ]));
        }

        $auth = new UserAuthService();

        $Roles = $this->fetchTable('Roles');
        $adminRole = $Roles->find()->where(['slug' => 'admin'])->first();

        $adminRoleId = $adminRole ? (int)$adminRole->id : 0;
        if ($adminRoleId <= 0) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'status' => false,
                    'messages' => __('ERROR__INTERNAL_ERROR'),
                ]));
        }

        $dataToSave = [
            'username' => (string)$data['username'],
            'email' => (string)$data['email'],
            'password' => (string)$data['password'],
            'role_id' => $adminRoleId,
        ];

        $userId = $auth->createUser($dataToSave, $ip);

        if ($userId <= 0) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'status' => false,
                    'messages' => __('ERROR__INTERNAL_ERROR'),
                ]));
        }

        InstallState::markInstalled();

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'status' => true,
                'messages' => __('USER__REGISTER_SUCCESS'),
                'redirect' => '/',
            ]));
    }
}
