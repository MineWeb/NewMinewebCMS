<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Filesystem\Folder;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;

class PluginController extends AppController
{
    function index()
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_PLUGINS'))
            throw new ForbiddenException();
        $this->set('title_for_layout', $this->Lang->get('PLUGIN__LIST'));
    }

    function admin_delete($id = false)
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_PLUGINS'))
            throw new ForbiddenException();
        if (!$id)
            throw new NotFoundException();
        $slug = $this->Plugin->find('first', ['conditions' => ['id' => $id]]);

        if (isset($slug['name']) && !$this->EyPlugin->delete($slug['name'])) {
            $this->History->set('DELETE_PLUGIN', 'plugin');
            $this->Session->setFlash($this->Lang->get('PLUGIN__DELETE_SUCCESS'), 'default.success');
        } else
            $this->Session->setFlash($this->Lang->get('ERROR__INTERNAL_ERROR'), 'default.error');

        Configure::write('Cache.disable', true);
        App::uses('Folder', 'Utility');
        $folder = new Folder(ROOT . DS . 'app' . DS . 'tmp' . DS . 'cache');
        if (!empty($folder->path)) {
            $folder->delete();
        }

        $this->redirect(['controller' => 'plugin', 'action' => 'index', 'admin' => true]);
    }

    function admin_enable($id = false)
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_PLUGINS'))
            throw new ForbiddenException();
        if (!$id)
            throw new NotFoundException();

        if ($this->EyPlugin->enable($id)) {
            $this->History->set('ENABLE_PLUGIN', 'plugin');
            $this->Session->setFlash($this->Lang->get('PLUGIN__ENABLE_SUCCESS'), 'default.success');
        } else
            $this->Session->setFlash($this->Lang->get('ERROR__INTERNAL_ERROR'), 'default.error');
        $this->redirect(['controller' => 'plugin', 'action' => 'index', 'admin' => true]);
    }

    function admin_disable($id = false)
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_PLUGINS'))
            throw new ForbiddenException();
        if (!$id)
            throw new NotFoundException();

        if ($this->EyPlugin->disable($id)) {
            $this->History->set('DISABLE_PLUGIN', 'plugin');
            $this->Session->setFlash($this->Lang->get('PLUGIN__DISABLE_SUCCESS'), 'default.success');
        } else
            $this->Session->setFlash($this->Lang->get('ERROR__INTERNAL_ERROR'), 'default.error');
        $this->redirect(['controller' => 'plugin', 'action' => 'index', 'admin' => true]);
    }

    function install($slug = false)
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_PLUGINS'))
            throw new ForbiddenException();
        if (!$slug)
            throw new NotFoundException();

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $installed = $this->EyPlugin->download($slug, true);
        if ($installed !== true)
            return $this->response->withStringBody(json_encode(['statut' => 'error', 'msg' => $this->Lang->get($installed)]));

        $this->History->set('INSTALL_PLUGIN', 'plugin');

        Configure::write('Cache.disable', true);
        $folder = new Folder(ROOT . DS . 'tmp' . DS . 'cache');
        if (!empty($folder->path)) {
            $folder->delete();
        }

        $this->Plugin = TableRegistry::getTableLocator()->get("Plugin");
        $this->Plugin->cacheQueries = false;
        $search = $this->Plugin->find('first', conditions: ['name' => $slug]);
        return $this->response->withStringBody(json_encode([
            'statut' => 'success',
            'plugin' => [
                'name' => $search['name'],
                'DBid' => $search['id'],
                'author' => $search['author'],
                'dateformatted' => $this->Lang->date($search['created']),
                'version' => $search['version']
            ]
        ]));
    }

    function admin_update($slug)
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_PLUGINS'))
            throw new ForbiddenException();
        if (!$slug)
            throw new NotFoundException();
        $this->autoRender = false;

        $updated = $this->EyPlugin->update($slug);
        if ($updated === true) {
            App::uses('Folder', 'Utility');
            $folder = new Folder(ROOT . DS . 'app' . DS . 'tmp' . DS . 'cache');
            if (!empty($folder->path)) {
                $folder->delete();
            }

            $this->History->set('UPDATE_PLUGIN', 'plugin');
            $this->Session->setFlash($this->Lang->get('PLUGIN__UPDATE_SUCCESS'), 'default.success');
        } else
            $this->Session->setFlash($this->Lang->get($updated), 'default.error');
        $this->redirect(['controller' => 'plugin', 'action' => 'index', 'admin' => true]);
    }

}
