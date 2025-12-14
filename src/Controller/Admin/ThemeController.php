<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use SplFileInfo;

/**
 * @property \App\Controller\Component\HistoryComponent $History
 */
class ThemeController extends AppController
{
    public function index(): ?Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_THEMES'))) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('THEME__LIST'));

        $this->set('themesAvailable', $this->themes->getThemesOnAPI(true, true));
        $this->set('themesInstalled', $this->themes->getThemesInstalled());

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Theme')
            ->setTemplate('index');

        return null;
    }

    public function enable(string $slug): Response
    {
        $this->disableAutoRender();

        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_THEMES'))) {
            throw new ForbiddenException();
        }

        if ($slug === '') {
            throw new NotFoundException();
        }

        $this->config->set('theme', $slug);
        $this->History->set('SET_THEME', 'theme');
        $this->Flash->success(__('THEME__ENABLED_SUCCESS'));

        return $this->redirect(['_name' => 'admin_theme_index']);
    }

    public function delete(string $slug): Response
    {
        $this->disableAutoRender();

        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_THEMES'))) {
            throw new ForbiddenException();
        }

        if ($slug === '') {
            throw new NotFoundException();
        }

        $current = (string)$this->config->get('theme');

        if ($current === $slug) {
            $this->Flash->error(__('THEME__CANT_DELETE_IF_ACTIVE'));

            return $this->redirect(['_name' => 'admin_theme_index']);
        }

        if ($this->themes->delete($slug)) {
            $this->History->set('DELETE_THEME', 'theme');
            $this->Flash->success(__('THEME__DELETE_SUCCESS'));
        } else {
            $this->Flash->error(__('ERROR__INTERNAL_ERROR'));
        }

        return $this->redirect(['_name' => 'admin_theme_index']);
    }

    public function install(string $slug): Response
    {
        $this->disableAutoRender();

        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_THEMES'))) {
            throw new ForbiddenException();
        }

        if ($slug === '') {
            throw new NotFoundException();
        }

        $error = $this->themes->install($slug);

        if ($error !== true) {
            $this->Flash->error(__((string)$error));

            return $this->redirect(['_name' => 'admin_theme_index']);
        }

        $this->History->set('INSTALL_THEME', 'theme');
        $this->Flash->success(__('THEME__INSTALL_SUCCESS'));

        return $this->redirect(['_name' => 'admin_theme_index']);
    }

    public function update(string $slug): Response
    {
        $this->disableAutoRender();

        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_THEMES'))) {
            throw new ForbiddenException();
        }

        if ($slug === '') {
            throw new NotFoundException();
        }

        $error = $this->themes->install($slug, true);

        if ($error !== true) {
            $this->Flash->error(__((string)$error));

            return $this->redirect(['_name' => 'admin_theme_index']);
        }

        $this->History->set('UPDATE_THEME', 'theme');
        $this->Flash->success(__('THEME__UPDATE_SUCCESS'));

        return $this->redirect(['_name' => 'admin_theme_index']);
    }

    public function custom(string $slug): ?Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_THEMES'))) {
            throw new ForbiddenException();
        }

        if ($slug === '') {
            throw new NotFoundException();
        }

        $this->set('title_for_layout', __('THEME__CUSTOMIZATION'));

        [$themeName, $config] = $this->themes->getCustomData($slug);
        $this->set(compact('config', 'themeName'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Theme')
            ->setTemplate('custom');

        $request = $this->getRequest();

        if ($request->is('post')) {
            if ($this->themes->processCustomData($slug, $request)) {
                $this->Flash->success(__('THEME__CUSTOMIZATION_SUCCESS'));
            }

            return $this->redirect([
                '_name' => 'admin_theme_custom',
                $slug,
            ]);
        }

        if ($slug !== 'default') {
            return $this->render($slug . '.Config/view');
        }

        return null;
    }

    public function customFiles(string $slug): ?Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_THEMES'))) {
            throw new ForbiddenException();
        }

        if ($slug === '') {
            throw new NotFoundException();
        }

        $this->set('title_for_layout', __('THEME__CUSTOM_FILES'));

        $cssFolder = $this->getCSSfolder($slug);

        $paths = findRecursive($cssFolder, ['css']);
        $cssFiles = [];

        foreach ($paths as $path) {
            $file = new SplFileInfo($path);
            $basename = substr($path, strlen($cssFolder));

            $cssFiles[] = [
                'basename' => $basename,
                'name' => $file->getFilename(),
            ];
        }

        $this->set('slug', $slug);
        $this->set('css_files', $cssFiles);

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Theme')
            ->setTemplate('custom_files');

        return null;
    }

    public function getCustomFile(string $slug): Response
    {
        $this->disableAutoRender();

        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_THEMES'))) {
            throw new ForbiddenException();
        }

        if ($slug === '') {
            throw new NotFoundException();
        }

        $args = func_get_args();
        unset($args[0]);
        $file = implode(DS, $args);
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $cssFolder = $this->getCSSfolder($slug);

        if (!file_exists($cssFolder . DS . $file) || $ext !== 'css') {
            throw new NotFoundException();
        }

        $content = @file_get_contents($cssFolder . DS . $file);

        return $this->response->withStringBody($content === false ? '' : $content);
    }

    public function saveCustomFile(string $slug): Response
    {
        $this->disableAutoRender();

        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_THEMES'))) {
            throw new ForbiddenException();
        }

        if ($slug === '') {
            throw new NotFoundException();
        }

        $file = (string)$this->getRequest()->getData('file', '');
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $content = (string)$this->getRequest()->getData('content', '');
        $cssFolder = $this->getCSSfolder($slug);

        if (!file_exists($cssFolder . DS . $file) || $ext !== 'css') {
            throw new NotFoundException();
        }

        @file_put_contents($cssFolder . DS . $file, $content);

        return $this->response->withStringBody(json_encode([
            'status' => true,
            'messages' => __('THEME__CUSTOM_FILES_FILE_CONTENT_SAVE_SUCCESS'),
        ]));
    }

    private function getCSSfolder(string $slug): string
    {
        if ($slug === 'default') {
            return ROOT . DS . 'webroot' . DS . 'css';
        }

        return ROOT . DS . 'plugins' . DS . 'Themes' . DS . $slug . DS . 'webroot' . DS . 'css';
    }
}
