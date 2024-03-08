<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

class APIController extends AppController {
    public function index()
    {
        if ($this->isConnected and $this->Permissions->can('MANAGE_API')) {
            $this->set('title_for_layout', $this->Lang->get('API__LABEL'));

            $this->ApiConfiguration = TableRegistry::getTableLocator()->get('ApiConfiguration');
            $config = $this->ApiConfiguration->find()->first();

            $this->Server = TableRegistry::getTableLocator()->get('Server');
            $get_all_servers = $this->Server->findSelectableServers(false);

            if ($this->request->is('post')) {
                if ($this->request->getData('skins') !== null and $this->request->getData('skin_free') !== null and !empty($this->request->getData('skin_filename')) and $this->request->getData('capes') !== null and $this->request->getData('cape_free') !== null and !empty($this->request->getData('cape_filename'))) {
                    $cfg = $this->ApiConfiguration->get(1);
                    $cfg->set($this->request->getData());
                    $this->ApiConfiguration->save($cfg);

                    $config = $this->request->getData();

                    $this->History->set('EDIT_CONFIGURATION', 'api');
                    $this->Flash->success($this->Lang->get('CONFIG__EDIT_SUCCESS'));
                } else {
                    $this->Flash->error($this->Lang->get('ERROR__FILL_ALL_FIELDS'));
                }
            }

            $this->set('get_all_servers', $get_all_servers);
            $this->set('config', $config);
        } else {
            $this->redirect('/');
        }
    }
}
