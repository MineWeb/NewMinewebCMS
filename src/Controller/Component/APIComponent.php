<?php
declare(strict_types=1);

namespace App\Controller\Component;

use App\Model\Table\ApiConfigurationsTable;
use App\Model\Table\ConfigurationsTable;
use App\Service\AuthService;
use Cake\Cache\Cache;
use Cake\Controller\Component;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use GdImage;

final class APIComponent extends Component
{
    use LocatorAwareTrait;

    public array $components = ['Session'];

    public bool $skin_active = false;
    public bool $cape_active = false;

    private AuthService $auth;
    private mixed $identity = null;
    private ApiConfigurationsTable $ApiConfigurations;
    private ConfigurationsTable $Configurations;

    private ?object $config = null;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->auth = new AuthService();

        $request = $this->getController()->getRequest();
        $this->identity = $this->auth->identity($request);

        $this->ApiConfigurations = $this->fetchTable('ApiConfigurations');
        $this->Configurations = $this->fetchTable('Configurations');

        $this->config = $this->ApiConfigurations->find()->first() ?: null;

        if ($this->config === null) {
            return;
        }

        $this->skin_active = (string)($this->config->skins ?? '0') === '1';
        $this->cape_active = (string)($this->config->capes ?? '0') === '1';
    }

    public function set(string $key, mixed $value): bool
    {
        $entity = $this->ApiConfigurations->get(1);
        $entity->set($key, $value);

        return (bool)$this->ApiConfigurations->save($entity);
    }

    public function can_skin(): bool
    {
        if (!$this->skin_active || $this->config === null) {
            return false;
        }

        if ((string)($this->config->skin_free ?? '0') === '1') {
            return true;
        }

        if (!is_object($this->identity) || !method_exists($this->identity, 'get')) {
            return false;
        }

        return ($this->identity->get('skin') ?? 0) === 1;
    }

    public function can_cape(): bool
    {
        if (!$this->cape_active || $this->config === null) {
            return false;
        }

        if ((string)($this->config->cape_free ?? '0') === '1') {
            return true;
        }

        if (!is_object($this->identity) || !method_exists($this->identity, 'get')) {
            return false;
        }

        return ($this->identity->get('cape') ?? 0) === 1;
    }

    public function get_skin(string $username): string
    {
        $rendered = imagecreatetruecolor(240, 480);
        if ($rendered === false) {
            return '';
        }

        $source = $this->getSkinImage($username);
        if ($source === false) {
            imagedestroy($rendered);

            return '';
        }

        $b = 120;
        $s = 8;

        $pink = imagecolorallocate($rendered, 255, 0, 255);
        imagefilledrectangle($rendered, 0, 0, 240, 480, $pink);
        imagecolortransparent($rendered, $pink);

        $sizeX = imagesx($source);
        $sizeY = imagesy($source);

        $temp = imagecreatetruecolor($sizeX, $sizeY);
        if ($temp === false) {
            imagedestroy($rendered);
            imagedestroy($source);

            return '';
        }

        imagecopyresampled($temp, $source, 0, 0, $sizeX - 1, 0, $sizeX, $sizeY, -$sizeX, $sizeY);

        imagecopyresampled($rendered, $source, $b / 2, 0, $s, $s, $b, $b, $s, $s);
        imagecopyresampled($rendered, $source, $b / 2, 0, $s * 5, $s, $b, $b, $s, $s);
        imagecopyresampled($rendered, $source, $b / 2, $b, (int)($s * 2.5), (int)($s * 2.5), $b, (int)($b * 1.5), $s, (int)($s * 1.5));
        imagecopyresampled($rendered, $source, (int)($b * 1.5), $b, (int)($s * 5.5), (int)($s * 2.5), (int)($b / 2), (int)($b * 1.5), (int)($s / 2), (int)($s * 1.5));
        imagecopyresampled($rendered, $temp, 0, $b, $s * 2, (int)($s * 2.5), (int)($b / 2), (int)($b * 1.5), (int)($s / 2), (int)($s * 1.5));
        imagecopyresampled($rendered, $source, 60, (int)($b * 2.5), (int)($s / 2), (int)($s * 2.5), (int)($b / 2), (int)($b * 1.5), (int)($s / 2), (int)($s * 1.5));
        imagecopyresampled($rendered, $temp, $b, (int)($b * 2.5), $s * 7, (int)($s * 2.5), (int)($b / 2), (int)($b * 1.5), (int)($s / 2), (int)($s * 1.5));

        ob_start();
        imagepng($rendered);
        $data = (string)ob_get_clean();

        imagedestroy($rendered);
        imagedestroy($source);
        imagedestroy($temp);

        return $data;
    }

    public function get_head_skin(string $username, int $size = 50): string
    {
        $src = $this->getSkinImage($username);
        if ($src === false) {
            return '';
        }

        $dest = imagecreatetruecolor(8, 8);
        if ($dest === false) {
            imagedestroy($src);

            return '';
        }

        imagecopy($dest, $src, 0, 0, 8, 8, 8, 8);

        $bgColor = imagecolorat($src, 0, 0);
        $noHelm = true;

        for ($i = 1; $i <= 8; $i++) {
            for ($j = 1; $j <= 4; $j++) {
                if (imagecolorat($src, 40 + $i, 7 + $j) !== $bgColor) {
                    $noHelm = false;
                    break;
                }
            }
            if (!$noHelm) {
                break;
            }
        }

        if (!$noHelm) {
            imagecopy($dest, $src, 0, -1, 40, 7, 8, 4);
        }

        $final = imagecreatetruecolor($size, $size);
        if ($final === false) {
            imagedestroy($dest);
            imagedestroy($src);

            return '';
        }

        imagecopyresized($final, $dest, 0, 0, 0, 0, $size, $size, 8, 8);

        ob_start();
        imagepng($final);
        $data = (string)ob_get_clean();

        imagedestroy($dest);
        imagedestroy($final);
        imagedestroy($src);

        return $data;
    }

    public function get(string $username, string $password, ?array $args = null): array
    {
        if ($username === '' || $password === '' || empty($args) || !is_array($args)) {
            return ['status' => false];
        }

        $args = array_values(array_unique(array_filter(array_map('strval', $args), static fn(string $v): bool => $v !== '')));
        if ($args === []) {
            return ['status' => false];
        }

        $user = $this->Users
            ->find()
            ->where([
                'pseudo' => $username,
                'password' => $password,
            ])
            ->first();

        if ($user === null) {
            return ['status' => false];
        }

        $out = [
            'status' => true,
            'args' => [],
        ];

        foreach ($args as $field) {
            if (property_exists($user, $field) || isset($user[$field])) {
                $out['args'][$field] = $user->{$field} ?? $user[$field] ?? null;
            }
        }

        return $out;
    }

    private function getSkinImage(string $username): GdImage|string|bool|null
    {
        $content = $this->getLocalSkinContent($username);

        if ($content === '' && $this->config !== null && ((string)($this->config->get_premium_skins ?? '0')) === '1') {
            $cacheKey = 'skin_' . $username;
            $cached = Cache::read($cacheKey, 'skin');

            if (is_string($cached) && $cached !== '') {
                $decoded = base64_decode($cached, true);
                if ($decoded !== false) {
                    $content = $decoded;
                }
            }

            if ($content === '') {
                $premium = $this->getSkinFromUsername($username);
                if (is_string($premium) && $premium !== '') {
                    $content = $premium;
                    Cache::write($cacheKey, base64_encode($content), 'skin');
                }
            }
        }

        if ($content !== '') {
            $img = @imagecreatefromstring($content);
            if ($img !== false) {
                return $img;
            }
        }

        $fallback = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAEAAAAAgCAMAAACVQ462AAAABGdBTUEAALGPC/xhBQAAAwBQTFRF' .
            'AAAAHxALIxcJJBgIJBgKJhgLJhoKJxsLJhoMKBsKKBsLKBoNKBwLKRwMKh0NKx4NKx4OLR0OLB4O' .
            'Lx8PLB4RLyANLSAQLyIRMiMQMyQRNCUSOigUPyoVKCgoPz8/JiFbMChyAFtbAGBgAGhoAH9/Qh0K' .
            'QSEMRSIOQioSUigmUTElYkMvbUMqb0UsakAwdUcvdEgvek4za2trOjGJUj2JRjqlVknMAJmZAJ6e' .
            'AKioAK+vAMzMikw9gFM0hFIxhlM0gVM5g1U7h1U7h1g6ilk7iFo5j14+kF5Dll9All9BmmNEnGNF' .
            'nGNGmmRKnGdIn2hJnGlMnWpPlm9bnHJcompHrHZaqn1ms3titXtnrYBttIRttolsvohst4Jyu4ly' .
            'vYtyvY5yvY50xpaA////AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
            'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
            'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
            'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
            'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
            'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
            'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
            'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAPSUN6AAAAQB0Uk5T////////////////////////' .
            '////////////////////////////////////////////////////////////////////////////' .
            '////////////////////////////////////////////////////////////////////////////' .
            '////////////////////////////////////////////////////////////////////////////' .
            '////////////////////////////////////////////////////////////////////////////' .
            '////////////AFP3ByUAAAAYdEVYdFNvZnR3YXJlAFBhaW50Lk5FVCB2My4zNqnn4iUAAAKjSURB' .
            'VEhLpZSLVtNAEIYLpSlLSUITLCBaGhNBQRM01M2mSCoXNUURIkZFxQvv/wz6724Wij2HCM7J6UyS' .
            '/b+dmZ208rsww6jiqo4FhannZb5yDqjaNgDVwE/8JAmCMqF6fwGwbU0CKjD/+oAq9jcM27gxAFpN' .
            'QxU3Bwi9Ajy8fgmGZuvaGAcIuwFA12CGce1jJESr6/Ot1i3Tnq5qptFqzet1jRA1F2XHWQFAs3Rz' .
            'wTTNhQd3rOkFU7c0DijmohRg1TR9ZmpCN7/8+PX954fb+sTUjK7VLKOYi1IAaTQtUrfm8pP88/vT' .
            'w8M5q06sZoOouSgHEDI5vrO/eHK28el04yxf3N8ZnyQooZiLfwA0arNb6d6bj998/+vx8710a7bW' .
            '4E2Uc1EKsEhz7WiQBK9eL29urrzsB8ngaK1JLDUXpYAkGSQH6e7640fL91dWXjxZ33138PZggA+S' .
            'z0WQlAL4gmewuzC1uCenqXevMPWc9XrMX/VXh6Hicx4ByHEeAfRg/wtgSMAvz+CKEkYAnc5SpwuD' .
            '4z70PM+hUf+4348ixF7EGItjxmQcCx/Dzv/SOkuXAF3PdT3GIujjGLELNYwxhF7M4oi//wsgdlYZ' .
            'dMXCmEUUSsSu0OOBACMoBTiu62BdRPEjYxozXFyIpK7IAE0IYa7jOBRqGlOK0BFq3Kdpup3DthFw' .
            'P9QDlBCGKEECoHEBEDLAXHAQMQnI8jwFYRQw3AMOQAJoOADoAVcDAh0HZAKQZUMZdC43kdeqAPwU' .
            'BEsC+M4cIEq5KEEBCl90mR8CVR3nxwCdBBS9OAe020UGnXb7KcxzPY9SXoEEIBZtgE7UDgBKyLMh' .
            'gBS2YdzjMJb4XHRDAPiQhSGjNOxKQIZTgC8BiMECgarxprjjO0OXiV4MAf4A/x0nbcyiS5EAAAAA' .
            'SUVORK5CYII=',
            true
        );

        if ($fallback === false) {
            Log::error('APIComponent fallback decode failed');

            return false;
        }

        $img = imagecreatefromstring($fallback);
        if ($img === false) {
            Log::error('APIComponent fallback imagecreatefromstring failed');
        }

        return $img;
    }

    private function getLocalSkinContent(string $username): string
    {
        if (!$this->skin_active || $this->config === null) {
            return '';
        }

        $template = (string)($this->config->skin_filename ?? '');
        if ($template === '') {
            return '';
        }

        $filename = str_replace('{PLAYER}', $username, $template);
        $path = WWW_ROOT . $filename . '.png';

        if (!is_file($path) || !is_readable($path)) {
            return '';
        }

        $fileContent = @file_get_contents($path);
        if ($fileContent === false) {
            return '';
        }

        return $fileContent;
    }

    private function getSkinFromUsername(string $username): string|false
    {
        $user = @json_decode((string)@file_get_contents('https://api.mojang.com/users/profiles/minecraft/' . $username), true);
        if (!is_array($user) || !isset($user['id'])) {
            return false;
        }

        $uuid = (string)$user['id'];

        $profile = @json_decode((string)@file_get_contents('https://sessionserver.mojang.com/session/minecraft/profile/' . $uuid), true);
        if (!is_array($profile) || !isset($profile['properties']) || !is_array($profile['properties'])) {
            return false;
        }

        $texturesValue = null;
        foreach ($profile['properties'] as $property) {
            if (is_array($property) && ($property['name'] ?? null) === 'textures' && isset($property['value'])) {
                $texturesValue = (string)$property['value'];
                break;
            }
        }

        if ($texturesValue === null || $texturesValue === '') {
            return false;
        }

        $decoded = base64_decode($texturesValue, true);
        if ($decoded === false) {
            return false;
        }

        $texturesObject = @json_decode($decoded, true);
        if (!is_array($texturesObject) || !isset($texturesObject['textures']['SKIN']['url'])) {
            return false;
        }

        $url = (string)$texturesObject['textures']['SKIN']['url'];
        if ($url === '') {
            return false;
        }

        $content = @file_get_contents($url);
        if ($content === false) {
            return false;
        }

        return $content;
    }
}
