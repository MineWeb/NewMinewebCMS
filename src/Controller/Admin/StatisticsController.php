<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;

class StatisticsController extends AppController
{
    public function index(): ?Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('VIEW_STATISTICS'))) {
            return $this->redirect('/');
        }

        $this->set('title_for_layout', __('STATS__TITLE'));

        $this->set('referers', $this->Visit->getGrouped('referer', 10));
        $this->set('pages', $this->Visit->getGrouped('page', 10));
        $this->set('language', $this->Visit->getGrouped('lang', 10));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Statistics')
            ->setTemplate('index');

        return null;
    }

    public function getVisits(): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('VIEW_STATISTICS'))) {
            throw new ForbiddenException();
        }

        $this->response = $this->response->withType('application/json');
        $this->disableAutoRender();

        $visits = $this->Visit->getVisitRange(15);
        $visitsFormatted = [];

        if ($visits) {
            foreach ($visits as $date => $count) {
                $timestamp = strtotime((string)$date);
                if ($timestamp === false) {
                    continue;
                }
                $visitsFormatted[] = [$timestamp * 1000, (int)$count];
            }
        }

        return $this->response->withStringBody(json_encode($visitsFormatted));
    }

    public function reset(): Response
    {
        $this->disableAutoRender();

        if (!($this->Auth->isConnected() && $this->Auth->can('VIEW_STATISTICS'))) {
            return $this->redirect('/');
        }

        $this->Visit->deleteAll(['1 = 1']);

        return $this->redirect(['_name' => 'admin_statistics_index']);
    }
}
