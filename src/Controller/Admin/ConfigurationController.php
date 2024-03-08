<?php
namespace App\Controller\Admin;

use App\Controller\AppController;

class ConfigurationController extends AppController
{
    public function index()
    {
        if ($this->isConnected and $this->Permissions->can('MANAGE_CONFIGURATION')) {
            $this->set('title_for_layout', $this->Lang->get('CONFIG__GENERAL_PREFERENCES'));

            $data = [];

            if ($this->request->is('post')) {
                foreach ($this->getRequest()->getData() as $k => $value) {
                    $data[$k] = $value == "" ? null : $value;
                }
                $hash = $this->Configuration->getKey('passwords_hash');
                $this->User->updateAll(
                    ['password_hash' => "'$hash'"],
                    ['password_hash IS NULL']
                );

                $data['end_layout_code'] = $this->getRequest()->getData('xss')['end_layout_code'];

                $config = $this->Configuration->get(1);
                $config->set($data);
                $this->Configuration->save($config);

                $this->History->set('EDIT_CONFIGURATION', 'configuration');

                $this->Configuration->cacheQueries = false; //On désactive le cache
                $this->Configuration->dataConfig = null;
                $this->Lang->lang = $this->Lang->getLang(); // on refresh les messages

                $this->Flash->success($this->Lang->get('CONFIG__EDIT_SUCCESS'));
            }

            $config = $this->Configuration->getAll();

            $this->Configuration->cacheQueries = true; //On le réactive

            $config['lang'] = $this->Lang->getLang('config')['path'];

            $config['languages_available'] = [];
            foreach ($this->Lang->languages as $key => $value) {
                $config['languages_available'][$key] = $value['name'];
            }

            $this->set('config', $config);

            $this->set('shopIsInstalled', $this->EyPlugin->isInstalled('eywek.shop'));

        } else {
            $this->redirect('/');
        }
    }

    public function editLang()
    {
        if ($this->isConnected and $this->Permissions->can('MANAGE_CONFIGURATION')) {
            if ($this->request->is('post')) {
                if (stripos($this->request->getData('GLOBAL__FOOTER'), '<a href="http://mineweb.org">mineweb.org</a>') === false) {
                    $this->Flash->error($this->Lang->get('CONFIG__ERROR_SAVE_LANG'));
                } else {
                    $this->Lang->setAll($this->request->getData());
                    $this->History->set('EDIT_LANG', 'lang');
                    $this->Flash->success($this->Lang->get('CONFIG__EDIT_LANG_SUCCESS'));
                }
            }

            $this->Lang->lang = $this->Lang->getLang(); // on refresh les messages

            $this->set('messages', $this->Lang->lang['messages']);
            $this->set('title_for_layout', $this->Lang->get('CONFIG__LANG_LABEL'));

        } else {
            $this->redirect('/');
        }
    }

}
