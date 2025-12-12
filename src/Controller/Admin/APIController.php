<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Response;

class APIController extends AppController
{
    public function index(): ?Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_API'))) {
            return $this->redirect('/');
        }

        $this->set('title_for_layout', __('API__LABEL'));

        $apiConfigurationTable = $this->fetchTable('ApiConfigurations');
        $config = $apiConfigurationTable->find()->first();

        $serverTable = $this->fetchTable('Servers');
        $get_all_servers = $serverTable->findSelectableServers(false);

        $request = $this->getRequest();

        if ($request->is('post')) {
            $data = $request->getData();

            $skins = $data['skins'] ?? null;
            $skinFree = $data['skin_free'] ?? null;
            $skinFilename = $data['skin_filename'] ?? null;
            $capes = $data['capes'] ?? null;
            $capeFree = $data['cape_free'] ?? null;
            $capeFilename = $data['cape_filename'] ?? null;

            if ($skins !== null
                && $skinFree !== null
                && !empty($skinFilename)
                && $capes !== null
                && $capeFree !== null
                && !empty($capeFilename)
            ) {
                $cfg = $apiConfigurationTable->get(1);
                $cfg->set($data);
                $apiConfigurationTable->saveOrFail($cfg);

                $config = $cfg;

                $this->History->set('EDIT_CONFIGURATION', 'api');
                $this->Flash->success(__('CONFIG__EDIT_SUCCESS'));
            } else {
                $this->Flash->error(__('ERROR__FILL_ALL_FIELDS'));
            }
        }

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Api')
            ->setTemplate('index');

        $this->set(compact('get_all_servers', 'config'));

        return null;
    }
}
