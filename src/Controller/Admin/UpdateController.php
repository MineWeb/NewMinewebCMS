<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class UpdateController extends AppController
{
    public function index(): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->User->isAdmin()) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('GLOBAL__UPDATE'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Update')
            ->setTemplate('index');

        return null;
    }

    public function clearCache(): Response
    {
        if (!$this->Auth->isConnected() || !$this->User->isAdmin()) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();

        $cachePath = ROOT . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'cache';
        $this->deleteDirectory($cachePath);

        return $this->redirect(['_name' => 'admin_update_index']);
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

    public function update(string $componentUpdated = '0'): Response
    {
        if (!$this->Auth->isConnected() || !$this->User->isAdmin()) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

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
            'msg' => __('UPDATE__SUCCESS'),
        ]));
    }

    public function check(): Response
    {
        if (!$this->Auth->isConnected() || !$this->User->isAdmin()) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();

        $file = ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'update';
        if (is_file($file)) {
            @unlink($file);
        }

        return $this->redirect(['_name' => 'admin_update_index']);
    }
}
