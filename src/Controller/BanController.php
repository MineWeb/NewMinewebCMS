<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * @property bool $isConnected
 * @property mixed $isBanned
 */
class BanController extends AppController
{
    public function index(): ?Response
    {
        if (!$this->isConnected || !$this->isBanned) {
            return $this->redirect('/');
        }

        $this->viewBuilder()
            ->setLayout('default')
            ->setTemplatePath('Ban')
            ->setTemplate('index');

        $this->set('title_for_layout', __('BAN__BAN'));
        $this->set('reason', $this->isBanned);

        return null;
    }

    public function ip(): ?Response
    {
        if (!$this->isBanned) {
            return $this->redirect('/');
        }

        $this->viewBuilder()
            ->setLayout('default')
            ->setTemplatePath('Ban')
            ->setTemplate('ip');

        $this->set('title_for_layout', __('BAN__BAN'));
        $this->set('reason', $this->isBanned);

        return null;
    }
}
