<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use App\Utility\LangService;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

class PluginController extends AppController
{
    public function index(): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_PLUGINS')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('PLUGIN__LIST'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Plugin')
            ->setTemplate('index');

        return null;
    }

    public function admin_delete(int|string|null $id = null): Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_PLUGINS')) {
            throw new ForbiddenException();
        }

        if ($id === null) {
            throw new NotFoundException();
        }

        $pluginTable = $this->fetchTable('Plugins');

        $plugin = $pluginTable
            ->find()
            ->where(['id' => $id])
            ->first();

        if ($plugin && isset($plugin['name']) && !$this->EyPlugin->delete($plugin['name'])) {
            $this->History->set('DELETE_PLUGIN', 'plugin');
            $this->Flash->success(__('PLUGIN__DELETE_SUCCESS'));
        } else {
            $this->Flash->error(__('ERROR__INTERNAL_ERROR'));
        }

        Configure::write('Cache.disable', true);
        Cache::clearAll();

        return $this->redirect(['_name' => 'admin_plugin_index']);
    }

    public function admin_enable(int|string|null $id = null): Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_PLUGINS')) {
            throw new ForbiddenException();
        }

        if ($id === null) {
            throw new NotFoundException();
        }

        if ($this->EyPlugin->enable($id)) {
            $this->History->set('ENABLE_PLUGIN', 'plugin');
            $this->Flash->success(__('PLUGIN__ENABLE_SUCCESS'));
        } else {
            $this->Flash->error(__('ERROR__INTERNAL_ERROR'));
        }

        return $this->redirect(['_name' => 'admin_plugin_index']);
    }

    public function admin_disable(int|string|null $id = null): Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_PLUGINS')) {
            throw new ForbiddenException();
        }

        if ($id === null) {
            throw new NotFoundException();
        }

        if ($this->EyPlugin->disable($id)) {
            $this->History->set('DISABLE_PLUGIN', 'plugin');
            $this->Flash->success(__('PLUGIN__DISABLE_SUCCESS'));
        } else {
            $this->Flash->error(__('ERROR__INTERNAL_ERROR'));
        }

        return $this->redirect(['_name' => 'admin_plugin_index']);
    }

    public function install(string $slug): Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_PLUGINS')) {
            throw new ForbiddenException();
        }

        if ($slug === '') {
            throw new NotFoundException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $installed = $this->EyPlugin->download($slug, true);
        if ($installed !== true) {
            return $this->response->withStringBody(json_encode([
                'status' => 'error',
                'message' => __($installed),
            ]));
        }

        $this->History->set('INSTALL_PLUGIN', 'plugin');

        Configure::write('Cache.disable', true);
        Cache::clearAll();

        $pluginTable = $this->fetchTable('Plugins');
        $pluginTable->cacheQueries(false);

        $plugin = $pluginTable
            ->find()
            ->where(['name' => $slug])
            ->first();

        if (!$plugin) {
            return $this->response->withStringBody(json_encode([
                'status' => 'error',
                'message' => __('ERROR__INTERNAL_ERROR'),
            ]));
        }

        return $this->response->withStringBody(json_encode([
            'status' => 'success',
            'plugin' => [
                'name' => $plugin['name'],
                'DBid' => $plugin['id'],
                'author' => $plugin['author'],
                'dateformatted' => LangService::date($plugin['created_at']),
                'version' => $plugin['version'],
            ],
        ]));
    }

    public function admin_update(string $slug): Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_PLUGINS')) {
            throw new ForbiddenException();
        }

        if ($slug === '') {
            throw new NotFoundException();
        }

        $this->disableAutoRender();

        $updated = $this->EyPlugin->update($slug);

        if ($updated === true) {
            Configure::write('Cache.disable', true);
            Cache::clearAll();

            $this->History->set('UPDATE_PLUGIN', 'plugin');
            $this->Flash->success(__('PLUGIN__UPDATE_SUCCESS'));
        } else {
            $this->Flash->error(__($updated));
        }

        return $this->redirect(['_name' => 'admin_plugin_index']);
    }
}
