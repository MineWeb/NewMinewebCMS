<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;
use SplFileInfo;

class LogController extends AppController
{
    public function error(): ?Response
    {
        if (!$this->isConnected || !$this->Permissions->can('PERMISSIONS__VIEW_WEBSITE_LOGS')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('LOG__VIEW_ERROR'));

        $filePath = LOGS . 'error.log';
        $errors = [];

        if (is_file($filePath)) {
            $file = new SplFileInfo($filePath);
            $handle = $file->openFile();

            $content = $handle->fread($file->getSize());
            $lines = explode("\n", $content);

            $index = 0;
            foreach ($lines as $line) {
                if (trim($line) === '') {
                    $index++;
                    continue;
                }
                $errors[$index][] = $line;
            }
        }

        $this->set('errorContent', $errors);

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Log')
            ->setTemplate('error');

        return null;
    }

    public function debug(): ?Response
    {
        if (!$this->isConnected || !$this->Permissions->can('PERMISSIONS__VIEW_WEBSITE_LOGS')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('LOG__VIEW_DEBUG'));

        $filePath = LOGS . 'debug.log';
        $debugs = [];

        if (is_file($filePath)) {
            $file = new SplFileInfo($filePath);
            $handle = $file->openFile();

            $content = $handle->fread($file->getSize());
            $lines = explode("\n", $content);

            $index = 0;
            foreach ($lines as $line) {
                if (trim($line) === '') {
                    $index++;
                    continue;
                }
                $debugs[$index][] = $line;
            }
        }

        $this->set('debugContent', $debugs);

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Log')
            ->setTemplate('debug');

        return null;
    }
}
