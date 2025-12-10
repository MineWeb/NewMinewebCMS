<?php
declare(strict_types=1);

use Migrations\AbstractSeed;

class ConfigurationSeed extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'website_url' => 'https://domain.fr',
                'name' => 'MineWeb',
                'email' => 'noreply@mineweb.org',
                'lang' => 'fr_FR',
                'theme' => 'default',
                'layout' => 'default',
                'money_name_singular' => 'point',
                'money_name_plural' => 'points',
                'server_state' => 0,
                'server_cache' => 0,
                'server_secretkey' => '',
                'server_timeout' => 1,
                'condition' => null,
                'banner_server' => serialize([]),
                'email_send_type' => 1,
                'smtpHost' => null,
                'smtpUsername' => null,
                'smtpPort' => null,
                'smtpPassword' => null,
                'google_analytics' => null,
                'end_layout_code' => null,
                'check_uuid' => 0,
                'captcha_type' => 1,
                'captcha_sitekey' => null,
                'captcha_secret' => null,
                'confirm_mail_signup' => 0,
                'confirm_mail_signup_block' => 0,
                'member_page_type' => 0,
                'passwords_hash' => 'bcrypt',
                'passwords_salt' => 0,
                'forced_updates' => 1,
                'session_type' => 'php',
                'microsoft_client_id' => null,
                'microsoft_client_secret' => null,
            ],
        ];

        $this->table('configurations')
            ->insert($data)
            ->saveData();
    }
}
