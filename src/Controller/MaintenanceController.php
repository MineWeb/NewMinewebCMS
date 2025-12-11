<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

class MaintenanceController extends AppController
{
    public function index(string $url = ''): Response
    {
        $this->set('title_for_layout', __('MAINTENANCE__TITLE'));

        $this->Maintenance = $this->fetchTable('Maintenances');
        $check = $this->Maintenance->checkMaintenance('/' . ltrim($url, '/'));

        if ($this->Permissions->can('BYPASS_MAINTENANCE') || !$check) {
            return $this->redirect('/');
        }

        $msg = $check['reason'] ?? '';
        $this->set(compact('msg'));

        $this->viewBuilder()
            ->setTemplatePath('Maintenance')
            ->setTemplate('index');

        return $this->render();
    }
}
