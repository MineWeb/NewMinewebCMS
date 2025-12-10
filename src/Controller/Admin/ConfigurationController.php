<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\I18n\I18n;
use App\Utility\LangService;

class ConfigurationController extends AppController
{
    public function index()
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_CONFIGURATION')) {
            return $this->redirect('/');
        }

        $this->set('title_for_layout', __('CONFIG__GENERAL_PREFERENCES'));

        if ($this->request->is('post')) {
            $data = [];
            foreach ($this->getRequest()->getData() as $k => $v) {
                $data[$k] = $v === '' ? null : $v;
            }

            $hash = $this->Configuration->getKey('passwords_hash');
            $this->User->updateAll(['password_hash' => "'$hash'"], ['password_hash IS NULL']);

            $data['end_layout_code'] = $this->getRequest()->getData('xss')['end_layout_code'] ?? null;

            $config = $this->Configuration->get(1);
            $config->set($data);
            $this->Configuration->save($config);

            $this->History->set('EDIT_CONFIGURATION', 'configuration');

            $this->Configuration->cacheQueries = false;
            $this->Configuration->dataConfig = null;

            $this->Flash->success(__('CONFIG__EDIT_SUCCESS'));
        }

        $config = $this->Configuration->getAll();
        $this->Configuration->cacheQueries = true;

        $config['lang'] = I18n::getLocale();
        $config['languages_available'] = $this->getAvailableLocales();

        $this->set('config', $config);
        $this->set('shopIsInstalled', $this->EyPlugin->isInstalled('eywek.shop'));
    }

    public function editLang()
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_CONFIGURATION')) {
            return $this->redirect('/');
        }

        if ($this->request->is('post')) {
            $footer = $this->request->getData('GLOBAL__FOOTER');

            if (stripos((string)$footer, '<a href="http://mineweb.org">mineweb.org</a>') === false) {
                $this->Flash->error(__('CONFIG__ERROR_SAVE_LANG'));
            } else {
                LangService::saveMany($this->request->getData());
                $this->History->set('EDIT_LANG', 'lang');
                $this->Flash->success(__('CONFIG__EDIT_LANG_SUCCESS'));
            }
        }

        $messages = LangService::loadCurrentMessages();
        $this->set('messages', $messages);
        $this->set('title_for_layout', __('CONFIG__LANG_LABEL'));
    }

    private function getAvailableLocales(): array
    {
        $path = ROOT . '/resources/locales';

        if (!is_dir($path)) {
            return [];
        }

        $dirs = scandir($path);
        if ($dirs === false) {
            return [];
        }

        $available = [];
        foreach ($dirs as $d) {
            if ($d === '.' || $d === '..') continue;
            if (is_dir($path . '/' . $d)) {
                $available[$d] = $d;
            }
        }

        return $available;
    }
}
