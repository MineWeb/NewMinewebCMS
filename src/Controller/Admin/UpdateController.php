<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * @property \App\Controller\Component\UpdateComponent $Update
 */
final class UpdateController extends AppController
{
    public function index(): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->isAdmin()) {
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
        if (!$this->Auth->isConnected() || !$this->Auth->isAdmin()) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();

        $cachePath = ROOT . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'cache';
        $this->deleteDirectory($cachePath);

        return $this->redirect(['_name' => 'admin_update_index']);
    }

    public function update(string $componentUpdated = '0'): Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->isAdmin()) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $isApplyStep = $componentUpdated === '1';

        if (!$this->Update->updateCMS($isApplyStep)) {
            return $this->response->withStringBody(json_encode([
                'status' => 'error',
                'messages' => $this->Update->errorUpdate,
            ]));
        }

        if (!$isApplyStep) {
            return $this->response->withStringBody(json_encode([
                'status' => 'continue',
                'messages' => '',
            ]));
        }

        return $this->response->withStringBody(json_encode([
            'status' => 'success',
            'messages' => __('UPDATE__SUCCESS'),
        ]));
    }

    public function check(): Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->isAdmin()) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();

        $this->Update->clearLatestCache();

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
}
