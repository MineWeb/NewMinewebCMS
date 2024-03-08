<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Log\Log;
use SplFileInfo;

class ThemeController extends AppController
{
    function index()
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_THEMES'))
            throw new ForbiddenException();

        $this->set('title_for_layout', $this->Lang->get('THEME__LIST'));

        $this->set('themesAvailable', $this->Theme->getThemesOnAPI(true, true));
        $this->set('themesInstalled', $this->Theme->getThemesInstalled());
    }

    function enable($slug = false)
    {
        $this->disableAutoRender();
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_THEMES'))
            throw new ForbiddenException();
        if (!$slug)
            throw new NotFoundException();

        $this->Configuration->setKey('theme', $slug);
        $this->History->set('SET_THEME', 'theme');
        $this->Flash->success($this->Lang->get('THEME__ENABLED_SUCCESS'));
        $this->redirect(['controller' => 'theme', 'action' => 'index', 'admin' => true]);
    }

    function delete($slug = false)
    {
        $this->disableAutoRender();
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_THEMES'))
            throw new ForbiddenException();
        if (!$slug)
            throw new NotFoundException();

        if ($this->Configuration->getKey('theme') == $slug) { // active theme
            $this->Flash->error($this->Lang->get('THEME__CANT_DELETE_IF_ACTIVE'));
            return $this->redirect(['controller' => 'theme', 'action' => 'index', 'admin' => true]);
        }

        clearDir(ROOT . '/templates/Themed/' . $slug);
        $this->History->set('DELETE_THEME', 'theme');
        $this->Flash->success($this->Lang->get('THEME__DELETE_SUCCESS'));
        return $this->redirect(['controller' => 'theme', 'action' => 'index', 'admin' => true]);
    }

    function install($slug = false)
    {
        $this->disableAutoRender();
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_THEMES'))
            throw new ForbiddenException();
        if (!$slug)
            throw new NotFoundException();
        // install
        $error = $this->Theme->install($slug);

        if ($error !== true) {
            $this->Flash->error($this->Lang->get($error));
            return $this->redirect(['controller' => 'theme', 'action' => 'index', 'admin' => true]);
        }

        $this->History->set('INSTALL_THEME', 'theme');
        $this->Flash->success($this->Lang->get('THEME__INSTALL_SUCCESS'));
        return $this->redirect(['controller' => 'theme', 'action' => 'index', 'admin' => true]);
    }

    function update($slug)
    {
        $this->disableAutoRender();
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_THEMES'))
            throw new ForbiddenException();
        if (!$slug)
            throw new NotFoundException();
        // install
        $error = $this->Theme->install($slug, true);
        if ($error !== true) {
            $this->Flash->error($this->Lang->get($error));
            return $this->redirect(['controller' => 'theme', 'action' => 'index', 'admin' => true]);
        }

        $this->History->set('UPDATE_THEME', 'theme');
        $this->Flash->success($this->Lang->get('THEME__UPDATE_SUCCESS'));
        return $this->redirect(['controller' => 'theme', 'action' => 'index', 'admin' => true]);
    }

    function custom($slug = false)
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_THEMES'))
            throw new ForbiddenException();
        if (!$slug)
            throw new NotFoundException();
        // config
        $this->set('title_for_layout', $this->Lang->get('THEME__CUSTOMIZATION'));
        list($theme_name, $config) = $this->Theme->getCustomData($slug);
        $this->set(compact('config', 'theme_name'));

        if ($this->request->is('post')) {
            if ($this->Theme->processCustomData($slug, $this->request)) // success save
                $this->Flash->success($this->Lang->get('THEME__CUSTOMIZATION_SUCCESS'));
            return $this->redirect(['controller' => 'theme', 'action' => 'custom', 'admin' => true, $slug]);
        }

        if ($slug != "default") // custom theme
            return $this->render(DS . 'Themed' . DS . $slug . DS . 'Config' . DS . 'view');
    }

    public function customFiles($slug)
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_THEMES'))
            throw new ForbiddenException();
        if (!$slug)
            throw new NotFoundException();
        // config
        $this->set('title_for_layout', $this->Lang->get('THEME__CUSTOM_FILES'));
        $CSSfolder = $this->getCSSfolder($slug);
        // each files
        $files = findRecursive($CSSfolder, array('css'));
        foreach ($files as $path) {
            $file = new SplFileInfo($path);
            $basename = substr($path, strlen($CSSfolder));

            $css_files[] = [
                'basename' => $basename,
                'name' => $file->getFilename()
            ];
        }

        $this->set(compact('slug', 'css_files'));
    }

    public function getCustomFile($slug)
    {
        $this->disableAutoRender();
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_THEMES'))
            throw new ForbiddenException();
        if (!$slug)
            throw new NotFoundException();
        // config
        $file = func_get_args();
        unset($file[0]);
        $file = implode(DS, $file);
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $CSSfolder = $this->getCSSfolder($slug);

        if (!file_exists($CSSfolder . DS . $file) || $ext != 'css')
            throw new NotFoundException();

        $get = @file_get_contents($CSSfolder . DS . $file);
        return $this->response->withStringBody($get);
    }

    public function saveCustomFile($slug)
    {
        $this->disableAutoRender();
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_THEMES'))
            throw new ForbiddenException();
        if (!$slug)
            throw new NotFoundException();
        // config
        $file = $this->getRequest()->getData('file');
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $content = $this->getRequest()->getData('content');
        $CSSfolder = $this->getCSSfolder($slug);

        if (!file_exists($CSSfolder . DS . $file) || $ext != 'css')
            throw new NotFoundException();

        @file_put_contents($CSSfolder . DS . $file, $content);
        return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('THEME__CUSTOM_FILES_FILE_CONTENT_SAVE_SUCCESS')]));
    }

    /**
     * @param $slug
     * @return string
     */
    public function getCSSfolder($slug)
    {
        if ($slug == "default")
            $CSSfolder = ROOT . DS . 'webroot' . DS . 'css';
        else
            $CSSfolder = ROOT . DS . 'templates' . DS . 'Themed' . DS . $slug . DS . 'webroot' . DS . 'css';
        return $CSSfolder;
    }

}
