<?php

use Migrations\AbstractMigration;

class CreateConfiguration extends AbstractMigration {
    public function change()
    {
        $table = $this->table('configurations', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table->addColumn('website_url', 'text', ['null' => false, 'default' => "https://domain.fr"]);
        $table->addColumn('name', 'string', ['null' => false, 'default' => null, 'length' => 50]);
        $table->addColumn('email', 'string', ['null' => false, 'default' => null, 'length' => 50]);
        $table->addColumn('lang', 'string', ['null' => false, 'default' => 'fr', 'length' => 5]);
        $table->addColumn('theme', 'string', ['null' => false, 'default' => 'default', 'length' => 50]);
        $table->addColumn('layout', 'string', ['null' => false, 'default' => null]);
        $table->addColumn('money_name_singular', 'string', ['null' => false, 'default' => null]);
        $table->addColumn('money_name_plural', 'string', ['null' => false, 'default' => null]);
        $table->addColumn('server_state', 'integer', ['null' => false, 'default' => null, 'length' => 1, 'signed' => false]);
        $table->addColumn('server_cache', 'integer', ['null' => false, 'default' => 0, 'length' => 1, 'signed' => false]);
        $table->addColumn('server_secretkey', 'string', ['null' => false, 'default' => null, 'length' => 50]);
        $table->addColumn('server_timeout', 'float', ['null' => false, 'default' => null, 'signed' => false]);
        $table->addColumn('usage_condition', 'string', ['null' => true, 'default' => null, 'length' => 250]);
        $table->addColumn('banner_server', 'text', ['null' => true, 'default' => null]);
        $table->addColumn('email_send_type', 'integer', ['null' => true, 'default' => '1', 'length' => 1, 'signed' => false, 'comment' => '1 = default, 2 = smtp']);
        $table->addColumn('smtpHost', 'string', ['null' => true, 'default' => null, 'length' => 30]);
        $table->addColumn('smtpUsername', 'string', ['null' => true, 'default' => null, 'length' => 150]);
        $table->addColumn('smtpPort', 'integer', ['null' => true, 'default' => null, 'length' => 5, 'signed' => false]);
        $table->addColumn('smtpPassword', 'string', ['null' => true, 'default' => null, 'length' => 100]);
        $table->addColumn('google_analytics', 'string', ['null' => true, 'default' => null, 'length' => 15]);
        $table->addColumn('end_layout_code', 'text', ['null' => true, 'default' => null]);
        $table->addColumn('check_uuid', 'integer', ['null' => true, 'default' => '0', 'length' => 1, 'signed' => false]);
        $table->addColumn('captcha_type', 'integer', ['null' => true, 'default' => '1', 'length' => 1, 'signed' => false, 'comment' => '1 = default, 2 = google, 3 = h-captcha']);
        $table->addColumn('captcha_sitekey', 'string', ['null' => true, 'default' => null, 'length' => 60]);
        $table->addColumn('captcha_secret', 'string', ['null' => true, 'default' => null, 'length' => 60]);
        $table->addColumn('confirm_mail_signup', 'integer', ['null' => false, 'default' => 0, 'length' => 1, 'signed' => false]);
        $table->addColumn('confirm_mail_signup_block', 'integer', ['null' => false, 'default' => 0, 'length' => 1, 'signed' => false]);
        $table->addColumn('member_page_type', 'integer', ['null' => false, 'default' => 0, 'length' => 1, 'signed' => false]);
        $table->addColumn('passwords_hash', 'string', ['null' => true, 'default' => null, 'length' => 10]);
        $table->addColumn('passwords_salt', 'integer', ['null' => true, 'default' => 0, 'length' => 1, 'signed' => false]);
        $table->addColumn('forced_updates', 'integer', ['null' => true, 'default' => 1, 'length' => 1, 'signed' => false]);
        $table->addColumn('session_type', 'string', ['null' => true, 'default' => null, 'length' => 10]);
        $table->addColumn('microsoft_client_id', 'string', array('null' => true, 'default' => null, 'length' => 50));
        $table->addColumn('microsoft_client_secret', 'string', array('null' => true, 'default' => null, 'length' => 50));

        $table->create();

        $table->insert([
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
            'email_send_type' => '1',
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
            'passwords_hash' => 'blowfish',
            'passwords_salt' => 0,
            'forced_updates' => 1,
            'session_type' => 'php',
            'microsoft_client_id' => null,
            'microsoft_client_secret' => null,
        ]);
        $table->saveData();
    }
}
