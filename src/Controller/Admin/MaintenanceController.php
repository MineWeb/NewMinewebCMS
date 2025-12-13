<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

class MaintenanceController extends AppController
{
    public function index(): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_MAINTENANCE')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('MAINTENANCE__TITLE'));

        $maintenanceTable = $this->fetchTable('Maintenances');
        $pages = $maintenanceTable->find()->all();

        $this->set('pages', $pages);

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Maintenance')
            ->setTemplate('index');

        return null;
    }

    public function add(): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_MAINTENANCE')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('MAINTENANCE__TITLE'));

        $maintenanceTable = $this->fetchTable('Maintenances');

        if ($this->getRequest()->is('post')) {
            $this->disableAutoRender();
            $this->response = $this->response->withType('application/json');

            if (!$this->getRequest()->getData('reason')) {
                return $this->response->withStringBody(json_encode([
                    'status' => false,
                    'messages' => __('MAINTENANCE__ADD_REASON_EMPTY'),
                ]));
            }

            $entity = $maintenanceTable->newEntity($this->getRequest()->getData());
            $maintenanceTable->saveOrFail($entity);

            return $this->response->withStringBody(json_encode([
                'status' => true,
                'messages' => __('MAINTENANCE__ADD_SUCCESS'),
            ]));
        }

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Maintenance')
            ->setTemplate('add');

        return null;
    }

    public function edit(int|string|null $id = null): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_MAINTENANCE') || !$id) {
            throw new ForbiddenException();
        }

        $maintenanceTable = $this->fetchTable('Maintenances');

        $page = $maintenanceTable
            ->find()
            ->where(['id' => $id])
            ->first();

        if (!$page) {
            throw new NotFoundException();
        }

        $this->set('title_for_layout', __('MAINTENANCE__TITLE'));
        $this->set('page', $page);

        if ($this->getRequest()->is('post')) {
            $this->disableAutoRender();
            $this->response = $this->response->withType('application/json');

            if (!$this->getRequest()->getData('reason')) {
                return $this->response->withStringBody(json_encode([
                    'status' => false,
                    'messages' => __('MAINTENANCE__ADD_REASON_EMPTY'),
                ]));
            }

            $page = $maintenanceTable->get($id);
            $page->set($this->getRequest()->getData());
            $maintenanceTable->saveOrFail($page);

            return $this->response->withStringBody(json_encode([
                'status' => true,
                'messages' => __('MAINTENANCE__EDIT_SUCCESS'),
            ]));
        }

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Maintenance')
            ->setTemplate('edit');

        return null;
    }

    public function disable(int|string|null $id = null): Response
    {
        $this->disableAutoRender();

        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_MAINTENANCE') || !$id) {
            throw new ForbiddenException();
        }

        $maintenanceTable = $this->fetchTable('Maintenances');

        $entity = $maintenanceTable->get($id);
        $entity->set('active', '0');
        $maintenanceTable->saveOrFail($entity);

        $this->Flash->success(__('MAINTENANCE__DISABLED_PAGE', [
            'PAGE' => $entity->url,
        ]));

        return $this->redirect(['_name' => 'admin_maintenance_index']);
    }

    public function enable(int|string|null $id = null): Response
    {
        $this->disableAutoRender();

        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_MAINTENANCE') || !$id) {
            throw new ForbiddenException();
        }

        $maintenanceTable = $this->fetchTable('Maintenances');

        $entity = $maintenanceTable->get($id);
        $entity->set('active', '1');
        $maintenanceTable->saveOrFail($entity);

        $this->Flash->success(__('MAINTENANCE__ENABLED_PAGE', [
            'PAGE' => $entity->url,
        ]));

        return $this->redirect(['_name' => 'admin_maintenance_index']);
    }

    public function delete(int|string|null $id = null): Response
    {
        $this->disableAutoRender();

        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_MAINTENANCE') || !$id) {
            throw new ForbiddenException();
        }

        $maintenanceTable = $this->fetchTable('Maintenances');
        $page = $maintenanceTable->get($id);
        $pageUrl = $page->url;

        $maintenanceTable->delete($page);

        $this->Flash->success(__('MAINTENANCE__DELETED_PAGE', [
            'PAGE' => $pageUrl,
        ]));

        return $this->redirect(['_name' => 'admin_maintenance_index']);
    }
}
