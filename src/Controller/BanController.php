<?php
namespace App\Controller;

class BanController extends AppController
{
    function index() {
        if (!$this->isConnected || !$this->isBanned) {
            $this->redirect("/");
            return;
        }

        $this->set('title_for_layout', __("BAN__BAN"));
        $this->set('reason', $this->isBanned);
    }

    function ip() {
        if (!$this->isBanned) {
            $this->redirect("/");
            return;
        }

        $this->set('title_for_layout', __("BAN__BAN"));
        $this->set('reason', $this->isBanned);
    }
}
