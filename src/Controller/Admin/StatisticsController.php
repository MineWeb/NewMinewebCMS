<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;

class StatisticsController extends AppController
{
    function index()
    {
        if ($this->isConnected and $this->Permissions->can('VIEW_STATISTICS')) {

            $this->set('title_for_layout', $this->Lang->get('STATS__TITLE'));
            $this->layout = 'admin';

            $this->set('referers', $this->Visit->getGrouped('referer', 10));
            $this->set('pages', $this->Visit->getGrouped('page', 10));
            $this->set('language', $this->Visit->getGrouped('lang', 10));
        } else {
            $this->redirect('/');
        }
    }

    function getVisits()
    {
        if ($this->isConnected and $this->Permissions->can('VIEW_STATISTICS')) {
            $this->response = $this->response->withType('application/json');

            $this->disableAutoRender();

            $visits = $this->Visit->getVisitRange(15);

            if ($visits) {
                foreach ($visits as $key => $value) {
                    $oldDate = strtotime($key);
                    $newDate = $oldDate * 1000;

                    $visitsFormatted[] = [$newDate, intval($value)];
                }

                return $this->response->withStringBody(json_encode($visitsFormatted));
            }

        } else {
            throw new ForbiddenException();
        }
    }

    function reset()
    {
        $this->disableAutoRender();
        if ($this->isConnected and $this->Permissions->can('VIEW_STATISTICS')) {
            $this->Visit->deleteAll(['1 = 1']);

            $this->redirect(['action' => 'index', 'admin' => true]);
        } else {
            $this->redirect('/');
        }
    }

}
