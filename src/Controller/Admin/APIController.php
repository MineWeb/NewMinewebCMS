<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;
use Cake\Log\Log;
use Throwable;

/**
 * @property \App\Controller\Component\AuthComponent $Auth
 * @property \App\Controller\Component\HistoryComponent $History
 */
class APIController extends AppController
{
    public function index(): ?Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_API'))) {
            return $this->redirect(['_name' => 'home']);
        }

        $this->set('title_for_layout', __('API__LABEL'));

        $apiConfigurations = $this->fetchTable('ApiConfigurations');
        $configEntity = $apiConfigurations->find()->first();

        $config = $configEntity ? $configEntity->toArray() : [];

        $serversTable = $this->fetchTable('Servers');
        $get_all_servers = $serversTable->findSelectableServers(false);

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Api')
            ->setTemplate('index');

        $this->set(compact('get_all_servers', 'config'));

        return null;
    }

    public function saveAjax(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_API'))) {
            throw new ForbiddenException();
        }

        $request = $this->getRequest();

        if (!$request->is('post')) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        $apiConfigurations = $this->fetchTable('ApiConfigurations');

        $cfg = $apiConfigurations->find()->first();
        if ($cfg === null) {
            $cfg = $apiConfigurations->newEmptyEntity();
        }

        $data = $request->getData();

        if (isset($data['skin_restorer_server_id'])) {
            $v = (string)$data['skin_restorer_server_id'];
            if ($v === '' || $v === '0') {
                $data['skin_restorer_server_id'] = null;
            }
        }

        Log::info('API Configuration data received: ' . json_encode($data));

        $cfg = $apiConfigurations->patchEntity($cfg, $data);

        if ($cfg->hasErrors()) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
                'errors' => $cfg->getErrors(),
            ]));
        }

        try {
            $apiConfigurations->saveOrFail($cfg);
        } catch (Throwable $e) {
            Log::error('API Configuration save failed: ' . $e->getMessage());

            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__INTERNAL'),
            ]));
        }

        $this->History->set('EDIT_CONFIGURATION', 'api');

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => __('CONFIG__EDIT_SUCCESS'),
        ]));
    }
}
