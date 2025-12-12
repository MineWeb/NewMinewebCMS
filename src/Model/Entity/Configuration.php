<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $website_url
 * @property string $name
 * @property string|null $email
 * @property string|null $lang
 * @property string|null $theme
 * @property string|null $layout
 * @property string|null $money_name_singular
 * @property string|null $money_name_plural
 * @property bool|null $server_state
 * @property bool|null $server_cache
 * @property string|null $server_secretkey
 * @property int|null $server_timeout
 * @property string|null $condition
 * @property string|null $banner_server
 * @property string|null $email_send_type
 * @property string|null $smtpHost
 * @property string|null $smtpUsername
 * @property int|null $smtpPort
 * @property string|null $smtpPassword
 * @property string|null $google_analytics
 * @property string|null $end_layout_code
 * @property bool|null $check_uuid
 * @property string|null $captcha_type
 * @property string|null $captcha_sitekey
 * @property string|null $captcha_secret
 * @property bool|null $confirm_mail_signup
 * @property bool|null $confirm_mail_signup_block
 * @property string|null $member_page_type
 * @property string|null $passwords_hash
 * @property string|null $passwords_salt
 * @property bool|null $forced_updates
 * @property string|null $session_type
 * @property string|null $microsoft_client_id
 * @property string|null $microsoft_client_secret
 */
class Configuration extends Entity
{
    protected array $_accessible = [
        'website_url' => true,
        'name' => true,
        'email' => true,
        'lang' => true,
        'theme' => true,
        'layout' => true,
        'money_name_singular' => true,
        'money_name_plural' => true,
        'server_state' => true,
        'server_cache' => true,
        'server_secretkey' => true,
        'server_timeout' => true,
        'condition' => true,
        'banner_server' => true,
        'email_send_type' => true,
        'smtpHost' => true,
        'smtpUsername' => true,
        'smtpPort' => true,
        'smtpPassword' => true,
        'google_analytics' => true,
        'end_layout_code' => true,
        'check_uuid' => true,
        'captcha_type' => true,
        'captcha_sitekey' => true,
        'captcha_secret' => true,
        'confirm_mail_signup' => true,
        'confirm_mail_signup_block' => true,
        'member_page_type' => true,
        'passwords_hash' => true,
        'passwords_salt' => true,
        'forced_updates' => true,
        'session_type' => true,
        'microsoft_client_id' => true,
        'microsoft_client_secret' => true,
    ];
}
