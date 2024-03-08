<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;

/**
 * Composant des statistiques
 * @author Eywek
 * Avec l'aide de : http://openclassrooms.com/courses/des-statistiques-pour-votre-site
 **/

/**
 * BDD
 *
 * -- Table visits --
 *
 * ip
 * created
 * referer
 * location
 *
 * -- Table rush_hours --
 *
 * created
 * visits
 *
 * -- connected --
 *
 * ip
 * created
 * location
 *
 **/

class StatisticsComponent extends Component
{
    private Controller $controller;

    function initialize(array $config): void
    {
        $this->controller = $this->getController();
    }

    function startup(Event $event)
    {
        $visit_check = $this->controller->getRequest()->getSession()->read("visit_check");
        if (!isset($visit_check) or empty($visit_check)) {
            $this->Visit = TableRegistry::getTableLocator()->get("Visit");
            $this->Util = $this->controller->Util;
            $ip = $this->Util->getIP();
            $visits = $this->Visit->find('all', conditions: ['ip' => $ip, 'created LIKE' => date('Y-m-d') . '%'])->toArray();
            if (empty($visits)) {
                if (!empty($_SERVER['HTTP_REFERER'])) {
                    $referer = htmlentities($_SERVER['HTTP_REFERER']);
                } else {
                    $referer = 'null';
                }
                $user_agent = (isset($_SERVER['HTTP_USER_AGENT'])) ? htmlentities($_SERVER['HTTP_USER_AGENT']) : 'null';
                $language = (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) ? htmlentities($_SERVER['HTTP_ACCEPT_LANGUAGE']) : 'null';

                $language = $language[0] . $language[1];
                $visit = $this->Visit->newEntity(['ip' => $ip, 'referer' => $referer, 'lang' => $language, 'navigator' => $user_agent, 'page' => "http://" . htmlentities($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])]);
                $this->Visit->save($visit);
            }

            $this->getController()->getRequest()->getSession()->write('visit_check', true);
        }
    }
}
