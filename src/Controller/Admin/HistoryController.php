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
        if (!$this->Permissions->can('VIEW_WEBSITE_HISTORY')) {
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
        if (!$this->Permissions->can('VIEW_WEBSITE_HISTORY')) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $historyTable = $this->fetchTable('Histories');

        $this->DataTable = $this->loadComponent('DataTable');
        $this->DataTable->setTable($historyTable);

        $this->paginate = [
            'contain' => ['User'],
            'fields' => [
                'History.id',
                'User.pseudo',
                'History.action',
                'History.user_id',
                'History.category',
                'History.created',
            ],
            'order' => 'History.id DESC',
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
                    'created' => LangService::date($history['created']),
                ],
                'User' => $history['user'],
            ];
        }

        $response['aaData'] = $data;

        return $this->response->withStringBody(json_encode($response));
    }
}
