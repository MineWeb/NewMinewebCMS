<?php
declare(strict_types=1);

namespace App\Controller\Component;

use Cake\Cache\Cache;
use Cake\Controller\Component;
use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class APIComponent extends Component
{
    public array $components = ['Session', 'Configuration'];

    public bool $skin_active = false;
    public bool $cape_active = false;

    private $controller;

    private Table $User;
    private Table $ApiConfiguration;

    private ?object $config = null;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->controller = $this->_registry->getController();
        $this->controller->set('API', $this);

        if (isset($this->controller->Configuration)) {
            $this->Configuration = $this->controller->Configuration;
        } else {
            $this->Configuration = TableRegistry::getTableLocator()->get('Configuration');
        }

        $locator = TableRegistry::getTableLocator();
        $this->User = $locator->get('User');
        $this->ApiConfiguration = $locator->get('ApiConfiguration');

        $this->config = $this->ApiConfiguration->find()->first() ?: null;

        $skinsEnabled = false;
        $capesEnabled = false;

        if ($this->config !== null) {
            $skinsEnabled = ((string)($this->config->skins ?? '0')) === '1';
            $capesEnabled = ((string)($this->config->capes ?? '0')) === '1';
        }

        $this->skin_active = $skinsEnabled;
        $this->cape_active = $capesEnabled;
    }

    public function set(string $key, mixed $value): bool
    {
        $config = $this->ApiConfiguration->get(1);
        $config->set([$key => $value]);

        return (bool)$this->ApiConfiguration->save($config);
    }

    public function can_skin(): bool
    {
        if (!$this->skin_active || $this->config === null) {
            return false;
        }

        if ((string)($this->config->skin_free ?? '0') === '1') {
            return true;
        }

        return (int)$this->User->getKey('skin') === 1;
    }

    public function can_cape(): bool
    {
        if (!$this->cape_active || $this->config === null) {
            return false;
        }

        if ((string)($this->config->cape_free ?? '0') === '1') {
            return true;
        }

        return (int)$this->User->getKey('cape') === 1;
    }

    public function get_skin(string $username): string
    {
        $rendered = imagecreatetruecolor(240, 480);
        if ($rendered === false) {
            return '';
        }

        $source = $this->_getSkinImage($username);
        if ($source === false) {
            imagedestroy($rendered);
            return '';
        }

        $b = 120;
        $s = 8;

        $pink = imagecolorallocate($rendered, 255, 0, 255);
        imagefilledrectangle($rendered, 0, 0, 240, 480, $pink);
        imagecolortransparent($rendered, $pink);

        $size_x = imagesx($source);
        $size_y = imagesy($source);

        $temp = imagecreatetruecolor($size_x, $size_y);
        if ($temp === false) {
            imagedestroy($rendered);
            imagedestroy($source);
            return '';
        }

        imagecopyresampled($temp, $source, 0, 0, $size_x - 1, 0, $size_x, $size_y, -$size_x, $size_y);
        $fsource = $temp;

        imagecopyresampled($rendered, $source, $b / 2, 0, $s, $s, $b, $b, $s, $s);
        imagecopyresampled($rendered, $source, $b / 2, 0, $s * 5, $s, $b, $b, $s, $s);
        imagecopyresampled($rendered, $source, $b / 2, $b, (int)($s * 2.5), (int)($s * 2.5), $b, (int)($b * 1.5), $s, (int)($s * 1.5));
        imagecopyresampled($rendered, $source, (int)($b * 1.5), $b, (int)($s * 5.5), (int)($s * 2.5), (int)($b / 2), (int)($b * 1.5), (int)($s / 2), (int)($s * 1.5));
        imagecopyresampled($rendered, $fsource, 0, $b, $s * 2, (int)($s * 2.5), (int)($b / 2), (int)($b * 1.5), (int)($s / 2), (int)($s * 1.5));
        imagecopyresampled($rendered, $source, 60, (int)($b * 2.5), (int)($s / 2), (int)($s * 2.5), (int)($b / 2), (int)($b * 1.5), (int)($s / 2), (int)($s * 1.5));
        imagecopyresampled($rendered, $fsource, $b, (int)($b * 2.5), $s * 7, (int)($s * 2.5), (int)($b / 2), (int)($b * 1.5), (int)($s / 2), (int)($s * 1.5));

        ob_start();
        imagepng($rendered);
        $data = (string)ob_get_clean();

        imagedestroy($rendered);
        imagedestroy($source);
        imagedestroy($temp);

        return $data;
    }

    private function _getSkinImage(string $username)
    {
        $content = '';

        if ($this->skin_active && $this->config !== null) {
            $filenameTemplate = (string)($this->config->skin_filename ?? '');
            if ($filenameTemplate !== '') {
                $filename = str_replace('{PLAYER}', $username, $filenameTemplate);
                $path = WWW_ROOT . $filename . '.png';
                if (is_file($path) && is_readable($path)) {
                    $fileContent = @file_get_contents($path);
                    if ($fileContent !== false) {
                        $content = $fileContent;
                    }
                }
            }
        }

        if ($content === '' && $this->config !== null && (string)($this->config->get_premium_skins ?? '0') === '1') {
            $cacheKey = 'skin_' . $username;
            $skin = Cache::read($cacheKey, 'skin');

            if ($skin === null) {
                $premiumContent = $this->_getSkinFromUsername($username);
                if ($premiumContent !== false && $premiumContent !== '') {
                    $content = $premiumContent;
                    Cache::remember($cacheKey, static function () use ($content) {
                        return base64_encode($content);
                    }, 'skin');
                }
            } else {
                $decoded = base64_decode($skin, true);
                if ($decoded !== false) {
                    $content = $decoded;
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
            'SUVORK5CYII='
        );

        if ($fallback === false) {
            Log::error('APIComponent: unable to decode fallback skin image');
            return false;
        }

        $img = imagecreatefromstring($fallback);
        if ($img === false) {
            Log::error('APIComponent: unable to create image from fallback skin data');
        }

        return $img;
    }

    private function _getSkinFromUsername(string $username): string|false
    {
        $user = @json_decode((string)@file_get_contents('https://api.mojang.com/users/profiles/minecraft/' . $username), true);
        if (!is_array($user) || !isset($user['id'])) {
            return false;
        }

        $uuid = $user['id'];

        $profile = @json_decode((string)@file_get_contents('https://sessionserver.mojang.com/session/minecraft/profile/' . $uuid), true);
        if (!is_array($profile) || !isset($profile['properties']) || !is_array($profile['properties'])) {
            return false;
        }

        $textures = null;
        foreach ($profile['properties'] as $property) {
            if (is_array($property) && isset($property['name'], $property['value']) && $property['name'] === 'textures') {
                $textures = $property;
                break;
            }
        }

        if ($textures === null) {
            return false;
        }

        $texturesObject = @json_decode((string)@base64_decode($textures['value']), true);
        if (!is_array($texturesObject) || !isset($texturesObject['textures']['SKIN']['url'])) {
            return false;
        }

        $url = $texturesObject['textures']['SKIN']['url'];
        $content = @file_get_contents($url);

        if ($content === false) {
            return false;
        }

        return $content;
    }

    public function get_head_skin(string $username, int $size = 50): string
    {
        $src = $this->_getSkinImage($username);
        if ($src === false) {
            return '';
        }

        $dest = imagecreatetruecolor(8, 8);
        if ($dest === false) {
            imagedestroy($src);
            return '';
        }

        imagecopy($dest, $src, 0, 0, 8, 8, 8, 8);

        $bg_color = imagecolorat($src, 0, 0);
        $no_helm = true;

        for ($i = 1; $i <= 8; $i++) {
            for ($j = 1; $j <= 4; $j++) {
                if (imagecolorat($src, 40 + $i, 7 + $j) !== $bg_color) {
                    $no_helm = false;
                    break;
                }
            }
            if (!$no_helm) {
                break;
            }
        }

        if (!$no_helm) {
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
        if ($username === '' || $password === '') {
            return ['status' => false];
        }

        if ($args === null || !is_array($args) || $args === []) {
            return ['status' => false];
        }

        $args = array_values(array_filter(array_map('strval', $args), static function (string $v): bool {
            return $v !== '';
        }));

        if ($args === []) {
            return ['status' => false];
        }

        $user = $this->User
            ->find()
            ->where([
                'pseudo' => $username,
                'password' => $password,
            ])
            ->first();

        if ($user === null) {
            return ['status' => false];
        }

        $result = [
            'status' => true,
            'args' => [],
        ];

        if (in_array('id', $args, true)) {
            $result['args']['id'] = $user->id ?? null;
        }

        if (in_array('email', $args, true)) {
            $result['args']['email'] = $user->email ?? null;
        }

        if (in_array('rank', $args, true)) {
            $result['args']['rank'] = $user->rank ?? null;
        }

        if (in_array('money', $args, true)) {
            $result['args']['money'] = $user->money ?? null;
        }

        if (in_array('ip', $args, true)) {
            $result['args']['ip'] = $user->ip ?? null;
        }

        if (in_array('vote', $args, true)) {
            $result['args']['vote'] = $user->vote ?? null;
        }

        if (in_array('created', $args, true)) {
            $result['args']['created'] = $user->created ?? null;
        }

        return $result;
    }
}
