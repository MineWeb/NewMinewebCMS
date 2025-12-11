<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

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
