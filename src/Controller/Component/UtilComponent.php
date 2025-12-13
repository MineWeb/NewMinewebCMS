<?php
declare(strict_types=1);

namespace App\Controller\Component;

use App\Model\Table\ConfigurationsTable;
use Cake\Controller\Component;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Exception\MissingDatasourceConfigException;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\Mailer\Mailer;
use Cake\Mailer\TransportFactory;
use Cake\ORM\Locator\LocatorAwareTrait;
use Exception;
use Laminas\Diactoros\UploadedFile;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use ZipArchive;

class UtilComponent extends Component
{
    use LocatorAwareTrait;

    private ?ConfigurationsTable $Configurations = null;

    private mixed $to = null;
    private mixed $from = null;
    private ?string $subject = null;
    private ?string $message = null;
    private string $typeSend = 'default';
    private array $smtpOptions = [];

    private mixed $dbType = 'mysql';
    private bool $dbAvailable = false;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->dbAvailable = $this->checkDatabaseAvailable();
    }

    private function checkDatabaseAvailable(): bool
    {
        try {
            ConnectionManager::getConfig('default');
        } catch (MissingDatasourceConfigException) {
            return false;
        }

        try {
            ConnectionManager::get('default');
        } catch (Exception) {
            return false;
        }

        return true;
    }

    public function startup(EventInterface $event): void
    {
        if (!$this->dbAvailable) {
            return;
        }

        try {
            $this->dbType = ConnectionManager::get('default')->getDriver();
        } catch (Exception) {
            $this->dbType = null;
        }
    }

    private function configurationsTable(): ?ConfigurationsTable
    {
        if (!$this->dbAvailable) {
            return null;
        }

        if ($this->Configurations !== null) {
            return $this->Configurations;
        }

        try {
            $table = $this->fetchTable('Configurations');
        } catch (Throwable) {
            return null;
        }

        if ($table instanceof ConfigurationsTable) {
            $this->Configurations = $table;

            return $this->Configurations;
        }

        if (method_exists($table, 'get')) {
            $this->Configurations = $table;

            return $this->Configurations;
        }

        return null;
    }

    private function configurationKey(string $key): mixed
    {
        $table = $this->configurationsTable();
        if ($table === null) {
            return null;
        }

        return $table->get($key);
    }

    public function getIP(): string
    {
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return htmlentities((string)$_SERVER['HTTP_CF_CONNECTING_IP']);
        }

        return isset($_SERVER['REMOTE_ADDR']) ? htmlentities((string)$_SERVER['REMOTE_ADDR']) : '0.0.0.0';
    }

    public function prepareMail(string $to, string $subject, string $message): self
    {
        $this->to = $to;
        $this->message = $message;

        $siteName = $this->configurationKey('name');
        $siteNameValue = is_string($siteName) && $siteName !== '' ? $siteName : null;

        $this->subject = $siteNameValue ? ($subject . ' | ' . $siteNameValue) : $subject;

        $fromEmail = $this->configurationKey('email');
        if (is_string($fromEmail) && $fromEmail !== '' && $siteNameValue) {
            $this->from = [$fromEmail => $siteNameValue];
        } else {
            $this->from = null;
        }

        $sendType = $this->configurationKey('email_send_type');
        $this->typeSend = !$sendType || (int)$sendType !== 2 ? 'default' : 'smtp';

        if ($this->typeSend === 'smtp') {
            $this->smtpOptions = [
                'className' => 'Smtp',
                'host' => (string)($this->configurationKey('smtpHost') ?? ''),
                'port' => (int)($this->configurationKey('smtpPort') ?? 0),
                'username' => (string)($this->configurationKey('smtpUsername') ?? ''),
                'password' => (string)($this->configurationKey('smtpPassword') ?? ''),
                'timeout' => 30,
            ];
        } else {
            $this->smtpOptions = [];
        }

        return $this;
    }

    public function sendMail(): bool
    {
        $mailer = new Mailer();

        if ($this->typeSend === 'smtp' && !empty($this->smtpOptions['host'])) {
            TransportFactory::setConfig('dynamic_smtp', $this->smtpOptions);
            $mailer->setTransport('dynamic_smtp');
        }

        if ($this->from !== null) {
            $mailer->setFrom($this->from);
        }

        if ($this->to !== null) {
            $mailer->setTo($this->to);
        }

        if ($this->subject !== null) {
            $mailer->setSubject($this->subject);
        }

        $mailer->viewBuilder()
            ->setTemplate('default')
            ->setLayout(null)
            ->setVar('message', $this->message);

        $theme = $this->configurationKey('theme');
        if (is_string($theme) && $theme !== '') {
            $mailer->viewBuilder()->setTheme($theme);
        }

        $mailer->setEmailFormat('html');

        try {
            return (bool)$mailer->deliver();
        } catch (Throwable $e) {
            $this->log($e->getMessage());

            return false;
        }
    }

    public function isValidReCaptcha(string $code, ?string $ip, string $secret, int $type = 2): bool
    {
        if ($code === '' || $secret === '') {
            return false;
        }

        $params = ['secret' => $secret, 'response' => $code];
        if ($ip) {
            $params['remoteip'] = $ip;
        }

        $website = '';
        if ($type === 2) {
            $website = 'https://www.google.com/recaptcha/api/siteverify';
        } elseif ($type === 3) {
            $website = 'https://hcaptcha.com/siteverify';
        }

        if ($website === '') {
            return false;
        }

        $url = $website . '?' . http_build_query($params);

        $response = null;

        if (function_exists('curl_version')) {
            $curl = curl_init($url);
            if ($curl === false) {
                return false;
            }
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 2);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
            $response = curl_exec($curl);
            curl_close($curl);
        } else {
            $context = stream_context_create(['http' => ['timeout' => 2]]);
            $response = @file_get_contents($url, false, $context);
        }

        if (empty($response)) {
            return false;
        }

        $json = json_decode((string)$response);

        return is_object($json) && !empty($json->success);
    }

    public function useSqlite(): bool
    {
        return $this->dbType instanceof Sqlite;
    }

    public function saveFolderInZIP(string $path, string $location, string $name): void
    {
        $rootPath = realpath($path);
        if ($rootPath === false) {
            return;
        }

        $zip = new ZipArchive();
        if ($zip->open($location . $name . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $filePath = $file->getRealPath();
            if ($filePath === false) {
                continue;
            }

            $relativePath = substr($filePath, strlen($rootPath) + 1);
            $zip->addFile($filePath, $relativePath);
        }

        $zip->close();
    }

    public function isValidImage(
        ServerRequest $request,
        array $extensions = ['png'],
        bool|int $width_max = false,
        bool|int $height_max = false,
        bool|int $max_size = false,
    ): array {
        $img = $request->getData('image');

        $msgEmpty = __('FORM__EMPTY_IMG');
        $msgNotUploaded = __('FORM__NOT_UPLOADED');
        $msgInvalid = __('FORM__INVALID_IMG');
        $msgInvalidExt = __('FORM__INVALID_EXTENSION');
        $msgTooHeavy = __('FORM__FILE_TOO_HEAVY');
        $msgInvalidWidth = __('FORM__INVALID_WIDTH');
        $msgInvalidHeight = __('FORM__INVALID_HEIGHT');

        if (!$img instanceof UploadedFile || $img->getClientFilename() === null || $img->getClientFilename() === '') {
            return ['status' => false, 'message' => $msgEmpty];
        }

        $uri = $img->getStream()->getMetadata('uri');

        if (!$img->getSize() || !$uri) {
            return ['status' => false, 'message' => $msgNotUploaded];
        }

        $extension = pathinfo($img->getClientFilename(), PATHINFO_EXTENSION);

        if (!in_array(strtolower((string)$extension), $extensions, true)) {
            $msg = str_replace('{LIST_EXTENSIONS}', implode(', ', $extensions), $msgInvalidExt);

            return ['status' => false, 'message' => $msg];
        }

        $infos = @getimagesize((string)$uri);

        if (!is_array($infos) || !isset($infos[0], $infos[1], $infos[2]) || $infos[2] < 1 || $infos[2] > 14) {
            return ['status' => false, 'message' => $msgInvalid];
        }

        if ($max_size) {
            $size = @filesize((string)$uri);
            if (is_int($size) && $size > $max_size) {
                $msg = str_replace('{MAX_SIZE}', (string)$max_size, $msgTooHeavy);

                return ['status' => false, 'message' => $msg];
            }
        }

        if ($width_max && $infos[0] > $width_max) {
            $msg = str_replace('{MAX_WIDTH}', (string)$width_max, $msgInvalidWidth);

            return ['status' => false, 'message' => $msg];
        }

        if ($height_max && $infos[1] > $height_max) {
            $msg = str_replace('{MAX_HEIGHT}', (string)$height_max, $msgInvalidHeight);

            return ['status' => false, 'message' => $msg];
        }

        return [
            'status' => true,
            'infos' => [
                'extension' => $extension,
                'width' => $infos[0],
                'height' => $infos[1],
            ],
        ];
    }

    public function uploadImage(ServerRequest $request, string $name): bool
    {
        $pathInfo = pathinfo($name);
        $path = $pathInfo['dirname'] ?? '.';

        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true) && !is_dir($path)) {
                return false;
            }
        }

        $file = $request->getData('image');
        if ($file instanceof UploadedFile) {
            $file->moveTo($name);

            return true;
        }

        return false;
    }
}
