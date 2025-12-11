<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use App\Utility\LangService;
use Cake\Http\Response;
use Cake\I18n\I18n;

class ConfigurationController extends AppController
{
    public function index(): ?Response
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_CONFIGURATION')) {
            return $this->redirect('/');
        }

        $this->set('title_for_layout', __('CONFIG__GENERAL_PREFERENCES'));

        $request = $this->getRequest();

        if ($request->is('post')) {
            $data = [];

            foreach ($request->getData() as $key => $value) {
                if ($key === '_csrfToken' || $key === 'xss') {
                    continue;
                }
                $data[$key] = $value === '' ? null : $value;
            }

            $hash = (string)$this->Configuration->getKey('passwords_hash');
            $this->User->updateAll(
                ['password_hash' => $hash],
                ['password_hash IS' => null]
            );

            $configEntity = $this->Configuration->get(1);
            $configEntity->set($data);
            $this->Configuration->saveOrFail($configEntity);

            $this->History->set('EDIT_CONFIGURATION', 'configuration');

            $this->Configuration->clearCache();

            $this->Flash->success(__('CONFIG__EDIT_SUCCESS'));
        }

        $config = $this->Configuration->getAll();

        if ($config !== null) {
            $config['lang'] = I18n::getLocale();
            $config['languages_available'] = $this->getAvailableLocales();
        }

        $this->set('config', $config);
        $this->set('shopIsInstalled', $this->EyPlugin->isInstalled('eywek.shop'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Configuration')
            ->setTemplate('index');

        return null;
    }

    public function editLang(): ?Response
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_CONFIGURATION')) {
            return $this->redirect('/');
        }

        $request = $this->getRequest();

        if ($request->is('post')) {
            $footer = (string)($request->getData('GLOBAL__FOOTER') ?? '');

            if (stripos($footer, '<a href="http://mineweb.org">mineweb.org</a>') === false) {
                $this->Flash->error(__('CONFIG__ERROR_SAVE_LANG'));
            } else {
                LangService::saveMany($request->getData());
                $this->History->set('EDIT_LANG', 'lang');
                $this->Flash->success(__('CONFIG__EDIT_LANG_SUCCESS'));
            }
        }

        $messages = LangService::loadCurrentMessages();

        $this->set('messages', $messages);
        $this->set('title_for_layout', __('CONFIG__LANG_LABEL'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Configuration')
            ->setTemplate('edit_lang');

        return null;
    }

    private function getAvailableLocales(): array
    {
        $path = ROOT . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'locales';

        if (!is_dir($path)) {
            return [];
        }

        $dirs = scandir($path);
        if ($dirs === false) {
            return [];
        }

        $available = [];

        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $dir;

            if (is_dir($fullPath)) {
                $available[$dir] = $dir;
            }
        }

        return $available;
    }
}
