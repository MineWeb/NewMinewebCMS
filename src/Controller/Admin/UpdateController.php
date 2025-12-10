<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

class UpdateController extends AppController
{
    public function index()
    {
        if (!$this->isConnected || !$this->User->isAdmin()) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', $this->Lang->get('GLOBAL__UPDATE'));
    }

    public function clearCache()
    {
        if (!$this->isConnected || !$this->User->isAdmin()) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();

        $cachePath = ROOT . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'cache';
        $this->deleteDirectory($cachePath);

        $this->redirect(['action' => 'index', 'admin' => true]);
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $file) {
            $filePath = $file->getPathname();
            if ($file->isDir()) {
                @rmdir($filePath);
            } else {
                @unlink($filePath);
            }
        }

        @rmdir($path);
    }

    public function update(string $componentUpdated = '0')
    {
        if (!$this->isConnected || !$this->User->isAdmin()) {
            throw new ForbiddenException();
        }

        $this->response = $this->response->withType('application/json');
        $this->disableAutoRender();

        $isComponentUpdated = $componentUpdated === '1';

        if (!$this->Update->updateCMS($isComponentUpdated)) {
            return $this->response->withStringBody(json_encode([
                'statut' => 'error',
                'msg' => $this->Update->errorUpdate,
            ]));
        }

        if (!$isComponentUpdated) {
            return $this->response->withStringBody(json_encode([
                'statut' => 'continue',
                'msg' => '',
            ]));
        }

        return $this->response->withStringBody(json_encode([
            'statut' => 'success',
            'msg' => $this->Lang->get('UPDATE__SUCCESS'),
        ]));
    }

    public function check()
    {
        if (!$this->isConnected || !$this->User->isAdmin()) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();

        $file = ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'update';
        if (is_file($file)) {
            @unlink($file);
        }

        $this->redirect(['action' => 'index', 'admin' => true]);
    }
}
