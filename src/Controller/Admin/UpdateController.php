<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Filesystem\Folder;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Log\Log;

class UpdateController extends AppController
{
    public function index()
    {
        if (!$this->isConnected || !$this->User->isAdmin())
            throw new ForbiddenException();

        $this->set('title_for_layout', $this->Lang->get('GLOBAL__UPDATE'));
    }

    public function clearCache()
    {
        if (!$this->isConnected || !$this->User->isAdmin())
            throw new ForbiddenException();

        $this->disableAutoRender();

        $folder = new Folder(ROOT . DS . 'tmp' . DS . 'cache');
        if (!empty($folder->path)) {
            $folder->delete();
        }

        $this->redirect(['action' => 'index', 'admin' => true]);
    }

    public function update($componentUpdated = '0')
    {
        if (!$this->isConnected || !$this->User->isAdmin())
            throw new ForbiddenException();

        $this->response = $this->response->withType('application/json');
        $this->disableAutoRender();

        if (!$this->Update->updateCMS($componentUpdated))
            return $this->response->withStringBody(json_encode(['statut' => 'error', 'msg' => $this->Update->errorUpdate]));
        if (!$componentUpdated)
            return $this->response->withStringBody(json_encode(['statut' => 'continue', 'msg' => '']));

        return $this->response->withStringBody(json_encode(['statut' => 'success', 'msg' => $this->Lang->get('UPDATE__SUCCESS')]));
    }

    public function check()
    {
        unlink(ROOT . '/config/update');
        $this->redirect(['action' => 'index', 'admin' => true]);
    }
}
