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
        $table->addColumn('condition', 'string', ['null' => true, 'default' => null, 'length' => 250]);
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
    }
}
