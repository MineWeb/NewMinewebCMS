<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use App\Utility\LangService;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;

class HistoryController extends AppController
{
    public function index(): ?Response
    {
        if (!$this->Auth->can('VIEW_WEBSITE_HISTORY')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('HISTORY__VIEW_GLOBAL'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/History')
            ->setTemplate('index');

        return null;
    }

    public function getAll(): Response
    {
        if (!$this->Auth->can('VIEW_WEBSITE_HISTORY')) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $historyTable = $this->fetchTable('Histories');

        $this->DataTable = $this->loadComponent('DataTable');
        $this->DataTable->setTable($historyTable);

        $this->paginate = [
            'contain' => ['Users'],
            'fields' => [
                'Histories.id',
                'Users.username',
                'Histories.action',
                'Histories.user_id',
                'Histories.category',
                'Histories.created_at',
            ],
            'order' => 'Histories.id DESC',
            'recursive' => 1,
        ];

        $this->DataTable->mDataProp = true;
        $response = $this->DataTable->getResponse();

        $data = [];
        foreach ($response['aaData'] as $history) {
            $data[] = [
                'History' => [
                    'action' => LangService::history($history['action']),
                    'category' => $history['category'],
                    'created_at' => LangService::date($history['created_at']),
                ],
                'User' => $history['user'],
            ];
        }

        $response['aaData'] = $data;

        return $this->response->withStringBody(json_encode($response));
    }
}
