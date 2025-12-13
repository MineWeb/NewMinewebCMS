<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

class BanController extends AppController
{
    public function index(): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->isBanned()) {
            return $this->redirect('/');
        }

        $this->set('title_for_layout', __('BAN__BAN'));
        $this->set('reason', $this->Auth->banReason());

        $this->viewBuilder()
            ->setLayout('default')
            ->setTemplatePath('Ban')
            ->setTemplate('index');

        return null;
    }

    public function ip(): ?Response
    {
        if (!$this->Auth->isBanned()) {
            return $this->redirect('/');
        }

        $this->set('title_for_layout', __('BAN__BAN'));
        $this->set('reason', $this->Auth->banReason());

        $this->viewBuilder()
            ->setLayout('default')
            ->setTemplatePath('Ban')
            ->setTemplate('ip');

        return null;
    }
}
