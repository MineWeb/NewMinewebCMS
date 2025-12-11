<?php

use Phinx\Migration\AbstractMigration;

class CreateConfiguration extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('configurations', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table
            ->addColumn('website_url', 'text', ['null' => false, 'default' => 'https://domain.fr'])
            ->addColumn('name', 'string', ['null' => false, 'length' => 50])
            ->addColumn('email', 'string', ['null' => false, 'length' => 50])
            ->addColumn('lang', 'string', ['null' => false, 'default' => 'fr', 'length' => 5])
            ->addColumn('theme', 'string', ['null' => false, 'default' => 'default', 'length' => 50])
            ->addColumn('layout', 'string', ['null' => false])
            ->addColumn('money_name_singular', 'string', ['null' => false])
            ->addColumn('money_name_plural', 'string', ['null' => false])
            ->addColumn('server_state', 'integer', ['null' => false, 'length' => 1, 'signed' => false])
            ->addColumn('server_cache', 'integer', ['null' => false, 'default' => 0, 'length' => 1, 'signed' => false])
            ->addColumn('server_secretkey', 'string', ['null' => false, 'length' => 50])
            ->addColumn('server_timeout', 'float', ['null' => false, 'signed' => false])
            ->addColumn('condition', 'string', ['null' => true, 'default' => null, 'length' => 250])
            ->addColumn('banner_server', 'text', ['null' => true, 'default' => null])
            ->addColumn('email_send_type', 'integer', ['null' => true, 'default' => 1, 'length' => 1, 'signed' => false, 'comment' => '1 = default, 2 = smtp'])
            ->addColumn('smtpHost', 'string', ['null' => true, 'default' => null, 'length' => 30])
            ->addColumn('smtpUsername', 'string', ['null' => true, 'default' => null, 'length' => 150])
            ->addColumn('smtpPort', 'integer', ['null' => true, 'default' => null, 'length' => 5, 'signed' => false])
            ->addColumn('smtpPassword', 'string', ['null' => true, 'default' => null, 'length' => 100])
            ->addColumn('google_analytics', 'string', ['null' => true, 'default' => null, 'length' => 15])
            ->addColumn('end_layout_code', 'text', ['null' => true, 'default' => null])
            ->addColumn('check_uuid', 'integer', ['null' => true, 'default' => 0, 'length' => 1, 'signed' => false])
            ->addColumn('captcha_type', 'integer', ['null' => true, 'default' => 1, 'length' => 1, 'signed' => false, 'comment' => '1 = default, 2 = google, 3 = h-captcha'])
            ->addColumn('captcha_sitekey', 'string', ['null' => true, 'default' => null, 'length' => 60])
            ->addColumn('captcha_secret', 'string', ['null' => true, 'default' => null, 'length' => 60])
            ->addColumn('confirm_mail_signup', 'integer', ['null' => false, 'default' => 0, 'length' => 1, 'signed' => false])
            ->addColumn('confirm_mail_signup_block', 'integer', ['null' => false, 'default' => 0, 'length' => 1, 'signed' => false])
            ->addColumn('member_page_type', 'integer', ['null' => false, 'default' => 0, 'length' => 1, 'signed' => false])
            ->addColumn('passwords_hash', 'string', ['null' => true, 'default' => null, 'length' => 10])
            ->addColumn('passwords_salt', 'integer', ['null' => true, 'default' => 0, 'length' => 1, 'signed' => false])
            ->addColumn('forced_updates', 'integer', ['null' => true, 'default' => 1, 'length' => 1, 'signed' => false])
            ->addColumn('session_type', 'string', ['null' => true, 'default' => null, 'length' => 10])
            ->addColumn('microsoft_client_id', 'string', ['null' => true, 'default' => null, 'length' => 50])
            ->addColumn('microsoft_client_secret', 'string', ['null' => true, 'default' => null, 'length' => 50])
            ->create();
    }
}
