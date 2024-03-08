<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use SplFileInfo;

class LogController extends AppController
{
    function error()
    {
        if (!$this->isConnected || !$this->Permissions->can("PERMISSIONS__VIEW_WEBSITE_LOGS"))
            throw new ForbiddenException();

        $this->set('title_for_layout', $this->Lang->get("LOG__VIEW_ERROR"));

        if (file_exists(LOGS . "error.log")) {
            $errorFile = new SplFileInfo(LOGS . "error.log");
            $fileObject = $errorFile->openFile();

            $errorContent = $fileObject->fread($errorFile->getSize());
            $errorContent = explode("\n", $errorContent);
            $errors = [];

            $errorNbr = 0;
            foreach ($errorContent as $line) {
                if ($line == "") {
                    $errorNbr++;
                    continue;
                }

                $errors[$errorNbr][] = $line;
            }

            $this->set("errorContent", $errors);
            $fileObject = null;
        }
    }

    function debug()
    {
        if (!$this->isConnected || !$this->Permissions->can("PERMISSIONS__VIEW_WEBSITE_LOGS"))
            throw new ForbiddenException();

        $this->set('title_for_layout', $this->Lang->get("LOG__VIEW_DEBUG"));

        if (file_exists(LOGS . "debug.log")) {
            $debugFile = new SplFileInfo(LOGS . "debug.log");
            $fileObject = $debugFile->openFile();

            $debugContent = $fileObject->fread($debugFile->getSize());
            $debugContent = explode("\n", $debugContent);
            $debugs = [];

            $debugNbr = 0;
            foreach ($debugContent as $line) {
                if ($line == "") {
                    $debugNbr++;
                    continue;
                }

                $debugs[$debugNbr][] = $line;
            }

            $this->set("debugContent", $debugs);
            $fileObject = null;
        }
    }
}
