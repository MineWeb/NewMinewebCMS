<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\ORM\TableRegistry;

/**
 * Composant qui gère les différents historiques
 **/

class HistoryComponent extends Component
{
    private $controller;

    function initialize(array $config): void
    {
        $this->controller = $this->_registry->getController();
        $this->controller->set('History', $this);
    }

    function startup($controller)
    {
    }

    function set($action, $category, $optionnal = null, $user_id = null)
    { // Ajoute une entrée dans l'historique général
        // j'inclue le fichier lang
        $this->User = TableRegistry::getTableLocator()->get("User");

        $user_id = (empty($user_id)) ? $this->User->getKey('id') : $user_id;

        $this->History = TableRegistry::getTableLocator()->get("History"); // le model history
        $history = $this->History->newEntity([
            'action' => $action,
            'category' => $category,
            'user_id' => $user_id,
            'other' => $optionnal
        ]);
        if ($this->History->save($history)) {
            return true;
        } else {
            return false;
        }
    }

    function get($category = false, $limit = false, $date = false, $action = false)
    { // récupére tout l'historique ou seulement une catégorie
        // j'inclue le fichier lang
        $this->History = TableRegistry::getTableLocator()->get("History"); // le model history

        if ($category) {
            $array['conditions']['category'] = $category;
        }
        if ($limit) {
            $array['limit'] = $limit;
        }
        if ($date) {
            $array['conditions']['created LIKE'] = $date . '%';
        }
        if ($action) {
            $array['conditions']['action'] = $action;
        }
        $array['order'] = 'id DESC';
        $search_history = $this->History->find('all', $array)->toArray();

        $i = 0;

        $this->Lang = $this->controller->Lang;

        foreach ($search_history as $value) { // je remplace les actions par leur traduction (ex: BUY_ITEM devient Achat d'un article)
            $search_history[$i]['action'] = str_replace($value['action'], $this->Lang->get($value['action']), $value['action']);
            $i++;
        }
        return $search_history;
    }

    function get_by_author($author)
    { // récupére tout l'historique d'un utilisateur
        // j'inclue le fichier lang
        $this->Lang = $this->controller->Lang;

        $this->History = TableRegistry::getTableLocator()->get("History"); // le model history
        $search_history = $this->History->find('all', conditions: ['author' => $author])->toArray(); // je cherche l'historique de l'utilisateur
        $i = 0;
        foreach ($search_history as $value) { // je remplace les actions par leur traduction (ex: BUY_ITEM devient Achat d'un article)
            $search_history[$i]['action'] = str_replace($value['action'], $this->Lang->get($value['action']), $value['action']);
            $i++;
        }
        return $search_history;
    }

}
