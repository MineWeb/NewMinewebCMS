<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\Mailer\Mailer;
use Cake\Network\Exception\SocketException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Security;
use Laminas\Diactoros\UploadedFile;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use ZipArchive;

class UtilComponent extends Component
{
    private $controller;

    private $to;
    private $from;
    private $subject;
    private $message;
    private $typeSend = 'default';

    private $smtpOptions = [];

    private $db_type = "mysql";

    function initialize(array $config): void
    {
        $this->controller = $this->_registry->getController();

        if (isset($this->controller->Configuration)) {
            $this->controller->Configuration = TableRegistry::getTableLocator()->get("Configuration");
        }

    }

    public function startup(Event $event) {
        $this->db_type = ConnectionManager::get("default")->getDriver();
    }

    // Get ip (support cloudfare)

    public function getIP()
    {
        return isset($_SERVER["HTTP_CF_CONNECTING_IP"]) ? htmlentities($_SERVER["HTTP_CF_CONNECTING_IP"]) : htmlentities($_SERVER["REMOTE_ADDR"]);
    }

    // Encoder un mot de passe

    public function password($password, $username, $hash_bcrypt = false, $hash = false)
    {
        $event = new Event('beforeEncodePassword', $this, ['password' => $password, 'username' => $username]);
        $this->controller->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            return $event->getResult();
        }

        if (!$hash)
            $hash = $this->getPasswordHashType();
        if (empty($hash)) {
            $hash = 'sha256';
        }
        $salt = false;

        if ($hash == 'blowfish') {
            if ($hash_bcrypt)
                $salt = $hash_bcrypt;
        } else {
            $salt = $this->controller->Configuration->getKey('passwords_salt');
            if (empty($salt)) {
                $salt = false;
            }
        }


        return Security::hash($password, $hash, $salt);
    }


    public function getPasswordHashType()
    {
        return $this->controller->Configuration->getKey('passwords_hash');
    }

    // Pour gérer les temps d'attente ou autre

    public function generateStringFromTime($waitTime)
    {
        $waitTime = $this->secondsToTime($waitTime);
        $time = [];
        if ($waitTime['d'] > 0)
            $time[] = $waitTime['d'] . ' ' . $this->controller->Lang->get('GLOBAL__DATE_R_DAYS');
        if ($waitTime['h'] > 0)
            $time[] = $waitTime['h'] . ' ' . $this->controller->Lang->get('GLOBAL__DATE_R_HOURS');
        if ($waitTime['m'] > 0)
            $time[] = $waitTime['m'] . ' ' . $this->controller->Lang->get('GLOBAL__DATE_R_MINUTES');
        if ($waitTime['s'] > 0)
            $time[] = $waitTime['s'] . ' ' . $this->controller->Lang->get('GLOBAL__DATE_R_SECONDS');
        return implode(', ', $time);
    }

    public function secondsToTime($inputSeconds)
    {

        $secondsInAMinute = 60;
        $secondsInAnHour = 60 * $secondsInAMinute;
        $secondsInADay = 24 * $secondsInAnHour;

        // extract days
        $days = floor($inputSeconds / $secondsInADay);

        // extract hours
        $hourSeconds = $inputSeconds % $secondsInADay;
        $hours = floor($hourSeconds / $secondsInAnHour);

        // extract minutes
        $minuteSeconds = $hourSeconds % $secondsInAnHour;
        $minutes = floor($minuteSeconds / $secondsInAMinute);

        // extract the remaining seconds
        $remainingSeconds = $minuteSeconds % $secondsInAMinute;
        $seconds = ceil($remainingSeconds);

        // return the final array
        return [
            'd' => (int)$days,
            'h' => (int)$hours,
            'm' => (int)$minutes,
            's' => (int)$seconds,
        ];
    }

    /*
      MAIL
    */

    public function prepareMail($to, $subject, $message)
    {

        $configuration = $this->controller->Configuration;

        $this->to = $to;

        $this->subject = $subject . ' | ' . $configuration->getKey('name');

        $this->message = $message;

        $this->from = [$configuration->getKey('email') => $configuration->getKey('name')];

        $this->typeSend = (!$configuration->getKey('email_send_type') || $configuration->getKey('email_send_type') != 2) ? 'default' : 'smtp';

        if ($this->typeSend == "smtp") {

            $this->smtpOptions['host'] = $configuration->getKey('smtpHost'); // smtp.sendgrid.net - ssl://smtp.gmail.com
            $this->smtpOptions['port'] = $configuration->getKey('smtpPort'); // 587 - 465
            $this->smtpOptions['username'] = $configuration->getKey('smtpUsername'); // Eywek
            $this->smtpOptions['password'] = $configuration->getKey('smtpPassword'); // motdepasse
            $this->smtpOptions['timeout'] = '30';
            //$this->smtpOptions['client'] = ''; // mineweb.org

        }

        return $this;
    }

    public function sendMail()
    {
        $this->Email = new Mailer();

        if ($this->typeSend == "smtp") {
            $this->Email->setTransport('Smtp');

            $this->Email->config($this->smtpOptions);
        }

        $this->Email->setFrom($this->from);
        $this->Email->setTo($this->to);
        $this->Email->setSubject($this->subject);
        $this->Email->setTemplate('default');
        $this->Email->setEmailFormat('html');
        $this->Email->setTheme($this->controller->Configuration->getKey('theme'));

        $event = new Event('beforeSendMail', $this, ['emailConfig' => $this->Email, 'message' => $this->message]);
        $this->controller->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            return $event->getResult();
        }

        $result = false;
        try {
            $result = $this->Email->send($this->message);
        } catch (SocketException $e) {
            $this->log($e->getMessage());
        }
        return $result;

    }

    public function in_array_r($needle, $haystack, $strict = false)
    {
        foreach ($haystack as $item) {
            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && $this->in_array_r($needle, $item, $strict))) {
                return true;
            }
        }

        return false;
    }

    public function isValidImage(ServerRequest $request, $extensions = ['png'], $width_max = false, $height_max = false, $max_size = false): array
    {
        /**
         * @var UploadedFile $img
         */
        $img = $request->getData('image');
        $Lang = $this->controller->Lang;

        if (empty($img->getClientFilename())) {
            return ['status' => false, 'msg' => $Lang->get('FORM__EMPTY_IMG')];
        }

        if (!$img->getSize() || !$img->getStream()->getMetadata('uri')) {
            return ['status' => false, 'msg' => $Lang->get('FORM__NOT_UPLOADED')];
        }

        $extension = pathinfo($img->getClientFilename(), PATHINFO_EXTENSION);

        if (!in_array(strtolower($extension), $extensions)) {
            return ['status' => false, 'msg' => str_replace('{LIST_EXTENSIONS}', implode(', ', $extensions), $Lang->get('FORM__INVALID_EXTENSION'))];
        }

        $infos = getimagesize($img->getStream()->getMetadata('uri'));

        if ($infos[2] < 1 || $infos[2] > 14 || $infos[0] === null || $infos[1] === null) {
            return ['status' => false, 'msg' => $Lang->get('FORM__INVALID_IMG')];
        }

        if ($max_size) {
            $size = filesize($img->getStream()->getMetadata('uri'));

            if ($size > $max_size) {
                return ['status' => false, 'msg' => str_replace('{MAX_SIZE}', $max_size, $Lang->get('FORM__FILE_TOO_HEAVY'))];
            }
        }

        if ($width_max) {
            if ($infos[0] > $width_max) {
                return ['status' => false, 'msg' => str_replace('{MAX_WIDTH}', $width_max, $Lang->get('FORM__INVALID_WIDTH'))];
            }
        }

        if ($height_max) {
            if ($infos[1] > $height_max) {
                return ['status' => false, 'msg' => str_replace('{MAX_HEIGHT}', $height_max, $Lang->get('FORM__INVALID_HEIGHT'))];
            }
        }

        return ['status' => true, 'infos' => ['extension' => $extension, 'width' => $infos[0], 'height' => $infos[1]]];

    }

    public function uploadImage(ServerRequest $request, $name)
    {
        $event = new Event('beforeUploadImage', $this, ['request' => $request, 'name' => $name]);
        $this->controller->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            return $event->getResult();
        }

        $path = pathinfo($name);
        $path = $path['dirname'];
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                return false;
            }
        }

        $request->getData('image')->moveTo($name);
        return true;
    }

    public function isValidReCaptcha($code, $ip, $secret, $type = 2)
    {
        if (empty($code)) {
            return false; // Si aucun code n'est entré, on s'arrete ici
        }
        $params = [
            'secret' => $secret, // Clé secrète a obtenir sur https://www.google.com/recaptcha/admin
            'response' => $code
        ];
        if ($ip) {
            $params['remoteip'] = $ip;
        }
        $website = "";

        switch ($type) {
            case 2:
                $website = "https://www.google.com/recaptcha/api/siteverify";
                break;
            case 3:
                $website = "https://hcaptcha.com/siteverify";
                break;
        }
        $url = "{$website}?" . http_build_query($params);
        if (function_exists('curl_version')) {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
            $response = curl_exec($curl);
        } else {
            $response = file_get_contents($url);
        }

        if (empty($response)) {
            return false;
        }

        $json = json_decode($response);
        return $json->success;
    }

    // Retourne un item aléatoire selon sa probabilité

    public function random($list, $probabilityTotal)
    {
        $pct = 1000;
        $rand = mt_rand(0, $pct);
        $items = [];
        $item = null;

        foreach ($list as $key => $value) {
            $items[$key] = $value / $probabilityTotal;
        }

        $i = 0;
        asort($items);

        foreach ($items as $name => $value) {
            $item = $name;
            if ($rand <= $i += ($value * $pct)) {
                $item = $name;
                break;
            }
        }
        return $item;
    }

    public function getDBType()
    {
        return $this->db_type;
    }

    public function useSqlite()
    {
        if ($this->getDBType() instanceof Sqlite)
            return true;

        return false;
    }

    public function saveFolderInZIP($path, $location, $name)
    {
        $rootPath = realpath($path);
        $zip = new ZipArchive();
        $zip->open($location . $name . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $file_path = $file->getRealPath();
                $relativePath = substr($file_path, strlen($rootPath) + 1);
                $zip->addFile($file_path, $relativePath);
            }
        }
        $zip->close();
    }
}
