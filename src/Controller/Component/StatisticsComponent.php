<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;

class StatisticsComponent extends Component
{
    private Controller $controller;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->controller = $this->getController();
    }

    public function startup(EventInterface $event): void
    {
        if (headers_sent()) {
            return;
        }

        $request = $this->controller->getRequest();

        if ($request->getAttribute('exception')) {
            return;
        }

        $session = $request->getSession();
        if ($session->check('visit_check')) {
            return;
        }

        $this->Visit = TableRegistry::getTableLocator()->get('Visits');
        $this->Util = $this->controller->Util;

        $ip = $this->Util->getIP();
        $visits = $this->Visit
            ->find('all', conditions: ['ip' => $ip, 'created LIKE' => date('Y-m-d') . '%'])
            ->toArray();

        if (empty($visits)) {
            $referer = !empty($_SERVER['HTTP_REFERER']) ? htmlentities($_SERVER['HTTP_REFERER']) : 'null';
            $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? htmlentities($_SERVER['HTTP_USER_AGENT']) : 'null';
            $languageHeader = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? htmlentities($_SERVER['HTTP_ACCEPT_LANGUAGE']) : 'null';
            $language = substr($languageHeader, 0, 2);
            $page = 'http://' . htmlentities($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

            $visit = $this->Visit->newEntity([
                'ip' => $ip,
                'referer' => $referer,
                'lang' => $language,
                'navigator' => $userAgent,
                'page' => $page,
            ]);

            $this->Visit->save($visit);
        }

        $session->write('visit_check', true);
    }
}
