<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Exception\MissingDatasourceConfigException;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\Mailer\Mailer;
use Cake\Mailer\TransportFactory;
use Cake\ORM\TableRegistry;
use Cake\Utility\Security;
use Exception;
use Laminas\Diactoros\UploadedFile;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class UtilComponent extends Component
{
    private $controller;

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

        $this->controller = $this->_registry->getController();
        $this->dbAvailable = $this->checkDatabaseAvailable();

        if ($this->dbAvailable && isset($this->controller->Configuration)) {
            $this->controller->Configuration = TableRegistry::getTableLocator()->get('Configurations');
        }
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
        if ($this->dbAvailable) {
            try {
                $this->dbType = ConnectionManager::get('default')->getDriver();
            } catch (Exception) {
                $this->dbType = null;
            }
        }
    }

    public function getIP(): string
    {
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return htmlentities($_SERVER['HTTP_CF_CONNECTING_IP']);
        }

        return isset($_SERVER['REMOTE_ADDR']) ? htmlentities($_SERVER['REMOTE_ADDR']) : '0.0.0.0';
    }

    public function password(string $password, string $username, ?string $existingHash = null, ?string $hash = null): bool|string
    {
        $event = new Event('beforeEncodePassword', $this, ['password' => $password, 'username' => $username]);
        $this->controller->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            return $event->getResult();
        }

        if ($hash === null) {
            $hash = $this->getPasswordHashType();
        }

        if (empty($hash)) {
            $hash = 'sha256';
        }

        if ($hash === 'blowfish' || $hash === 'bcrypt') {
            if ($existingHash !== null) {
                return password_verify($password, $existingHash);
            }

            return password_hash($password, PASSWORD_BCRYPT);
        }

        $salt = false;
        if ($this->hasConfiguration()) {
            $salt = $this->controller->Configuration->getKey('passwords_salt') ?? false;
        }

        return Security::hash($password, $hash, $salt);
    }

    private function hasConfiguration(): bool
    {
        if (!$this->dbAvailable) {
            return false;
        }

        if (!isset($this->controller->Configuration)) {
            return false;
        }

        return is_object($this->controller->Configuration) && method_exists($this->controller->Configuration, 'getKey');
    }

    public function getPasswordHashType(): ?string
    {
        if ($this->hasConfiguration()) {
            return $this->controller->Configuration->getKey('passwords_hash');
        }

        return 'bcrypt';
    }

    public function generateStringFromTime(int $waitTime): string
    {
        $waitTime = $this->secondsToTime($waitTime);
        $time = [];


        if ($waitTime['d'] > 0) {
            $label = __('GLOBAL__DATE_R_DAYS');
            $time[] = $waitTime['d'] . ' ' . $label;
        }
        if ($waitTime['h'] > 0) {
            $label = __('GLOBAL__DATE_R_HOURS');
            $time[] = $waitTime['h'] . ' ' . $label;
        }
        if ($waitTime['m'] > 0) {
            $label = __('GLOBAL__DATE_R_MINUTES');
            $time[] = $waitTime['m'] . ' ' . $label;
        }
        if ($waitTime['s'] > 0) {
            $label = __('GLOBAL__DATE_R_SECONDS');
            $time[] = $waitTime['s'] . ' ' . $label;
        }

        return implode(', ', $time);
    }

    public function secondsToTime(int $inputSeconds): array
    {
        $secondsInAMinute = 60;
        $secondsInAnHour = 60 * $secondsInAMinute;
        $secondsInADay = 24 * $secondsInAnHour;

        $days = (int)floor($inputSeconds / $secondsInADay);
        $hourSeconds = $inputSeconds % $secondsInADay;
        $hours = (int)floor($hourSeconds / $secondsInAnHour);

        $minuteSeconds = $hourSeconds % $secondsInAnHour;
        $minutes = (int)floor($minuteSeconds / $secondsInAMinute);

        $remainingSeconds = $minuteSeconds % $secondsInAMinute;
        $seconds = (int)ceil($remainingSeconds);

        return [
            'd' => $days,
            'h' => $hours,
            'm' => $minutes,
            's' => $seconds,
        ];
    }

    public function prepareMail($to, string $subject, string $message): self
    {
        if (!$this->hasConfiguration()) {
            $this->to = $to;
            $this->subject = $subject;
            $this->message = $message;
            $this->from = null;
            $this->typeSend = 'default';
            $this->smtpOptions = [];
            return $this;
        }

        $configuration = $this->controller->Configuration;

        $this->to = $to;
        $this->subject = $subject . ' | ' . (string)$configuration->getKey('name');
        $this->message = $message;
        $this->from = [
            $configuration->getKey('email') => $configuration->getKey('name'),
        ];

        $sendType = $configuration->getKey('email_send_type');
        $this->typeSend = (!$sendType || (int)$sendType !== 2) ? 'default' : 'smtp';

        if ($this->typeSend === 'smtp') {
            $this->smtpOptions = [
                'className' => 'Smtp',
                'host' => $configuration->getKey('smtpHost'),
                'port' => $configuration->getKey('smtpPort'),
                'username' => $configuration->getKey('smtpUsername'),
                'password' => $configuration->getKey('smtpPassword'),
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

        if ($this->hasConfiguration()) {
            $theme = $this->controller->Configuration->getKey('theme');
            if ($theme) {
                $mailer->viewBuilder()->setTheme($theme);
            }
        }

        $mailer->setEmailFormat('html');

        try {
            return (bool)$mailer->deliver();
        } catch (\Throwable $e) {
            $this->log($e->getMessage());
            return false;
        }
    }

    public function in_array_r(mixed $needle, array $haystack, bool $strict = false): bool
    {
        foreach ($haystack as $item) {
            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && $this->in_array_r($needle, $item, $strict))) {
                return true;
            }
        }

        return false;
    }

    public function isValidImage(ServerRequest $request, array $extensions = ['png'], bool|int $width_max = false, bool|int $height_max = false, bool|int $max_size = false): array
    {
        $img = $request->getData('image');

        $msgEmpty = __('FORM__EMPTY_IMG');
        $msgNotUploaded = __('FORM__NOT_UPLOADED');
        $msgInvalid = __('FORM__INVALID_IMG');
        $msgInvalidExt = __('FORM__INVALID_EXTENSION');
        $msgTooHeavy = __('FORM__FILE_TOO_HEAVY');
        $msgInvalidWidth = __('FORM__INVALID_WIDTH');
        $msgInvalidHeight = __('FORM__INVALID_HEIGHT');

        if (!$img instanceof UploadedFile || empty($img->getClientFilename())) {
            return ['status' => false, 'msg' => $msgEmpty];
        }

        $uri = $img->getStream()->getMetadata('uri');

        if (!$img->getSize() || !$uri) {
            return ['status' => false, 'msg' => $msgNotUploaded];
        }

        $extension = pathinfo($img->getClientFilename(), PATHINFO_EXTENSION);

        if (!in_array(strtolower((string)$extension), $extensions, true)) {
            $msg = str_replace('{LIST_EXTENSIONS}', implode(', ', $extensions), $msgInvalidExt);
            return ['status' => false, 'msg' => $msg];
        }

        $infos = @getimagesize($uri);

        if (!is_array($infos) || !isset($infos[0], $infos[1], $infos[2]) || $infos[2] < 1 || $infos[2] > 14) {
            return ['status' => false, 'msg' => $msgInvalid];
        }

        if ($max_size) {
            $size = filesize($uri);
            if ($size > $max_size) {
                $msg = str_replace('{MAX_SIZE}', (string)$max_size, $msgTooHeavy);
                return ['status' => false, 'msg' => $msg];
            }
        }

        if ($width_max && $infos[0] > $width_max) {
            $msg = str_replace('{MAX_WIDTH}', (string)$width_max, $msgInvalidWidth);
            return ['status' => false, 'msg' => $msg];
        }

        if ($height_max && $infos[1] > $height_max) {
            $msg = str_replace('{MAX_HEIGHT}', (string)$height_max, $msgInvalidHeight);
            return ['status' => false, 'msg' => $msg];
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

    public function uploadImage(ServerRequest $request, string $name): mixed
    {
        $event = new Event('beforeUploadImage', $this, ['request' => $request, 'name' => $name]);
        $this->controller->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            return $event->getResult();
        }

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

    public function isValidReCaptcha(string $code, ?string $ip, string $secret, int $type = 2): bool
    {
        if ($code === '') {
            return false;
        }

        $params = [
            'secret' => $secret,
            'response' => $code,
        ];
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

        if (function_exists('curl_version')) {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
            $response = curl_exec($curl);
            curl_close($curl);
        } else {
            $response = @file_get_contents($url);
        }

        if (empty($response)) {
            return false;
        }

        $json = json_decode($response);
        return is_object($json) && !empty($json->success);
    }

    public function random(array $list, float|int $probabilityTotal): string|int|null
    {
        $pct = 1000;
        $rand = mt_rand(0, $pct);
        $items = [];
        $item = null;

        foreach ($list as $key => $value) {
            $items[$key] = $probabilityTotal > 0 ? $value / $probabilityTotal : 0;
        }

        $i = 0;
        asort($items);

        foreach ($items as $name => $value) {
            $item = $name;
            $i += $value * $pct;
            if ($rand <= $i) {
                break;
            }
        }

        return $item;
    }

    public function getDBType(): mixed
    {
        return $this->dbType;
    }

    public function useSqlite(): bool
    {
        return $this->getDBType() instanceof Sqlite;
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
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                if ($filePath === false) {
                    continue;
                }
                $relativePath = substr($filePath, strlen($rootPath) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
    }
}
