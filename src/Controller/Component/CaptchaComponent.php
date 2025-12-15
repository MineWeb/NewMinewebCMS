<?php
declare(strict_types=1);

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Http\CallbackStream;
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

    public function showImage(array $custom = []): CallbackStream
    {
        $settings = array_merge($this->getConfig(), $custom);

        return $this->buildStream($settings);
    }

    private function buildStream(array $settings): CallbackStream
    {
        $image = imagecreatetruecolor((int)$settings['winWidth'], (int)$settings['winHeight']);
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
                imagesetpixel($image, rand(0, (int)$settings['winWidth']), rand(0, (int)$settings['winHeight']), $tempColor);
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
        $rY1 = (int)((float)$settings['winHeight'] / 1.8);
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

        if ((bool)$settings['bgNoise']) {
            $image = $this->applyWave($image, (int)$settings['winWidth'], (int)$settings['winHeight']);
        }

        if ((bool)$settings['lineNoise']) {
            $width = (int)$settings['winWidth'];
            for ($i = 0; $i < $width; $i++) {
                if ($i % 10 === 0) {
                    imageline($image, $i, 0, $i + 10, 50, $charColor);
                    imageline($image, $i, 0, $i - 10, 50, $charColor);
                }
            }
        }

        return new CallbackStream(function () use ($image): void {
            imagepng($image);
            imagedestroy($image);
        });
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

    private function applyWave($image, int $width, int $height)
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
}
