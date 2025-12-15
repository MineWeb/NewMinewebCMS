<?php
declare(strict_types=1);

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Http\Response;
use RuntimeException;

final class CaptchaComponent extends Component
{
    protected array $_defaultConfig = [
        'characters' => null,
        'winHeight' => 50,
        'winWidth' => 320,
        'fontSize' => 25,
        'fontPath' => 'tahomabd.ttf',
        'bgNoise' => false,
        'lineNoise' => false,
        'bgColor' => '#F58220',
        'noiseColor' => '#000',
        'textColor' => '#fff',
        'noiseLevel' => 45,
    ];

    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        parent::__construct($registry, $config);
    }

    public function showImage(array $custom = []): Response
    {
        $settings = array_merge($this->getConfig(), $custom);
        $png = $this->buildPng($settings);

        $response = new Response();
        $response = $response->withType('png');
        $response = $response->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response = $response->withHeader('Pragma', 'no-cache');
        $response = $response->withHeader('Expires', '0');

        return $response->withStringBody($png);
    }

    private function buildPng(array $settings): string
    {
        $width = (int)$settings['winWidth'];
        $height = (int)$settings['winHeight'];

        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            throw new RuntimeException('Cannot initialize GD image stream');
        }

        $bgColor = $this->hex2rgb((string)$settings['bgColor']);
        $noiseColor = $this->hex2rgb((string)$settings['noiseColor']);
        $textColor = $this->hex2rgb((string)$settings['textColor']);

        $bg = imagecolorallocate($image, $bgColor[0], $bgColor[1], $bgColor[2]);
        imagefill($image, 10, 10, $bg);

        $noiseLevel = (int)$settings['noiseLevel'];
        for ($x = 0; $x < $noiseLevel; $x++) {
            for ($y = 0; $y < $noiseLevel; $y++) {
                $tempColor = imagecolorallocate($image, $noiseColor[0], $noiseColor[1], $noiseColor[2]);
                imagesetpixel($image, rand(0, $width), rand(0, $height), $tempColor);
            }
        }

        $charColor = imagecolorallocatealpha($image, $textColor[0], $textColor[1], $textColor[2], 0);

        $characters = $settings['characters'];
        if ($characters === null || $characters === '') {
            $characters = (string)mt_rand(100, 10000);
        }
        $characters = (string)$characters;

        $font = (string)$settings['fontPath'];
        $fontSize = (int)$settings['fontSize'];

        $rX1 = 10;
        $rX2 = 20;
        $rY1 = (int)($height / 1.8);
        $rY2 = $rY1 + 10;

        $len = strlen($characters);
        for ($i = 0; $i < $len; $i++) {
            $char = $characters[$i];
            $randomX = mt_rand($rX1, $rX2);
            $randomY = mt_rand($rY1, $rY2);
            $randomAngle = mt_rand(-20, 20);

            imagettftext($image, $fontSize, $randomAngle, $randomX, $randomY, $charColor, $font, $char);

            $rX1 += 40;
            $rX2 += 40;
        }

        if (!empty($settings['bgNoise'])) {
            $image = $this->applyWave($image, $width, $height);
        }

        if (!empty($settings['lineNoise'])) {
            for ($i = 0; $i < $width; $i++) {
                if ($i % 10 === 0) {
                    imageline($image, $i, 0, $i + 10, 50, $charColor);
                    imageline($image, $i, 0, $i - 10, 50, $charColor);
                }
            }
        }

        ob_start();
        imagepng($image);
        $png = (string)ob_get_clean();
        imagedestroy($image);

        return $png;
    }

    private function hex2rgb(string $hex): array
    {
        $hex = str_replace('#', '', $hex);

        if (strlen($hex) === 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));

            return [$r, $g, $b];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return [$r, $g, $b];
    }

    private function applyWave(mixed $image, int $width, int $height): mixed
    {
        $xPeriod = 10;
        $yPeriod = 10;
        $yAmplitude = 5;
        $xAmplitude = 5;

        $xp = $xPeriod * rand(1, 3);
        $k = rand(0, 100);
        for ($a = 0; $a < $width; $a++) {
            imagecopy($image, $image, $a - 1, (int)(sin($k + $a / $xp) * $xAmplitude), $a, 0, 1, $height);
        }

        $yp = $yPeriod * rand(1, 2);
        $k = rand(0, 100);
        for ($a = 0; $a < $height; $a++) {
            imagecopy($image, $image, (int)(sin($k + $a / $yp) * $yAmplitude), $a - 1, 0, $a, $width, 1);
        }

        return $image;
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
}
