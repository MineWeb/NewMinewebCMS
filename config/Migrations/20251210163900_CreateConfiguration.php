<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateConfiguration extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('configurations');

        $table
            ->addColumn('website_url', 'text', ['null' => false, 'default' => 'https://domain.fr'])
            ->addColumn('name', 'string', ['null' => false, 'limit' => 50])
            ->addColumn('email', 'string', ['null' => false, 'limit' => 50])
            ->addColumn('lang', 'string', ['null' => false, 'default' => 'fr', 'limit' => 5])
            ->addColumn('theme', 'string', ['null' => false, 'default' => 'default', 'limit' => 50])
            ->addColumn('layout', 'string', ['null' => false])
            ->addColumn('money_name_singular', 'string', ['null' => false])
            ->addColumn('money_name_plural', 'string', ['null' => false])
            ->addColumn('server_state', 'integer', ['null' => false, 'limit' => 1, 'signed' => false])
            ->addColumn('server_cache', 'integer', ['null' => false, 'default' => 0, 'limit' => 1, 'signed' => false])
            ->addColumn('server_secretkey', 'string', ['null' => false, 'limit' => 50])
            ->addColumn('server_timeout', 'float', ['null' => false, 'signed' => false])
            ->addColumn('condition', 'string', ['null' => true, 'default' => null, 'limit' => 250])
            ->addColumn('banner_server', 'text', ['null' => true, 'default' => null])
            ->addColumn('email_send_type', 'integer', ['null' => true, 'default' => 1, 'limit' => 1, 'signed' => false, 'comment' => '1 = default, 2 = smtp'])
            ->addColumn('smtpHost', 'string', ['null' => true, 'default' => null, 'limit' => 30])
            ->addColumn('smtpUsername', 'string', ['null' => true, 'default' => null, 'limit' => 150])
            ->addColumn('smtpPort', 'integer', ['null' => true, 'default' => null, 'limit' => 5, 'signed' => false])
            ->addColumn('smtpPassword', 'string', ['null' => true, 'default' => null, 'limit' => 100])
            ->addColumn('google_analytics', 'string', ['null' => true, 'default' => null, 'limit' => 15])
            ->addColumn('end_layout_code', 'text', ['null' => true, 'default' => null])
            ->addColumn('check_uuid', 'integer', ['null' => true, 'default' => 0, 'limit' => 1, 'signed' => false])
            ->addColumn('captcha_type', 'integer', ['null' => true, 'default' => 1, 'limit' => 1, 'signed' => false, 'comment' => '1 = default, 2 = google, 3 = h-captcha'])
            ->addColumn('captcha_sitekey', 'string', ['null' => true, 'default' => null, 'limit' => 60])
            ->addColumn('captcha_secret', 'string', ['null' => true, 'default' => null, 'limit' => 60])
            ->addColumn('confirm_mail_signup', 'integer', ['null' => false, 'default' => 0, 'limit' => 1, 'signed' => false])
            ->addColumn('confirm_mail_signup_block', 'integer', ['null' => false, 'default' => 0, 'limit' => 1, 'signed' => false])
            ->addColumn('member_page_type', 'integer', ['null' => false, 'default' => 0, 'limit' => 1, 'signed' => false])
            ->addColumn('passwords_hash', 'string', ['null' => true, 'default' => null, 'limit' => 10])
            ->addColumn('passwords_salt', 'integer', ['null' => true, 'default' => 0, 'limit' => 1, 'signed' => false])
            ->addColumn('forced_updates', 'integer', ['null' => true, 'default' => 1, 'limit' => 1, 'signed' => false])
            ->addColumn('session_type', 'string', ['null' => true, 'default' => null, 'limit' => 10])
            ->addColumn('microsoft_client_id', 'string', ['null' => true, 'default' => null, 'limit' => 50])
            ->addColumn('microsoft_client_secret', 'string', ['null' => true, 'default' => null, 'limit' => 50])
            ->addTimestamps()
            ->create();
    }
}
