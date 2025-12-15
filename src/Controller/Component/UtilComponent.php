<?php
declare(strict_types=1);

namespace App\Controller\Component;

use App\Service\ConfigurationService;
use Cake\Controller\Component;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Exception\MissingDatasourceConfigException;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\Mailer\Mailer;
use Cake\Mailer\TransportFactory;
use Exception;
use Laminas\Diactoros\UploadedFile;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use ZipArchive;

final class UtilComponent extends Component
{
    private ConfigurationService $configuration;

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
        $this->configuration = new ConfigurationService();
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

    public function prepareMail(string $to, string $subject, string $message): self
    {
        $this->to = $to;
        $this->message = $message;

        $siteNameValue = $this->configuration->getWebsiteName();
        $this->subject = $siteNameValue !== '' ? ($subject . ' | ' . $siteNameValue) : $subject;

        $fromEmail = $this->configuration->get('email');
        if (is_string($fromEmail) && $fromEmail !== '' && $siteNameValue !== '') {
            $this->from = [$fromEmail => $siteNameValue];
        } else {
            $this->from = null;
        }

        $sendType = $this->configuration->get('email_send_type');
        $this->typeSend = is_numeric($sendType) && (int)$sendType === 2 ? 'smtp' : 'default';

        if ($this->typeSend === 'smtp') {
            $this->smtpOptions = [
                'className' => 'Smtp',
                'host' => (string)($this->configuration->get('smtpHost') ?? ''),
                'port' => (int)($this->configuration->get('smtpPort') ?? 0),
                'username' => (string)($this->configuration->get('smtpUsername') ?? ''),
                'password' => (string)($this->configuration->get('smtpPassword') ?? ''),
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

        $theme = $this->configuration->getThemeName();
        if ($theme !== '') {
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
            return ['status' => false, 'messages' => $msgEmpty];
        }

        $uri = $img->getStream()->getMetadata('uri');

        if (!$img->getSize() || !$uri) {
            return ['status' => false, 'messages' => $msgNotUploaded];
        }

        $extension = pathinfo($img->getClientFilename(), PATHINFO_EXTENSION);

        if (!in_array(strtolower((string)$extension), $extensions, true)) {
            $msg = str_replace('{LIST_EXTENSIONS}', implode(', ', $extensions), $msgInvalidExt);

            return ['status' => false, 'messages' => $msg];
        }

        $infos = @getimagesize((string)$uri);

        if (!is_array($infos) || !isset($infos[0], $infos[1], $infos[2]) || $infos[2] < 1 || $infos[2] > 14) {
            return ['status' => false, 'messages' => $msgInvalid];
        }

        if ($max_size) {
            $size = @filesize((string)$uri);
            if (is_int($size) && $size > $max_size) {
                $msg = str_replace('{MAX_SIZE}', (string)$max_size, $msgTooHeavy);

                return ['status' => false, 'messages' => $msg];
            }
        }

        if ($width_max && $infos[0] > $width_max) {
            $msg = str_replace('{MAX_WIDTH}', (string)$width_max, $msgInvalidWidth);

            return ['status' => false, 'messages' => $msg];
        }

        if ($height_max && $infos[1] > $height_max) {
            $msg = str_replace('{MAX_HEIGHT}', (string)$height_max, $msgInvalidHeight);

            return ['status' => false, 'messages' => $msg];
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
        $path = (string)($pathInfo['dirname'] ?? '.');

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
