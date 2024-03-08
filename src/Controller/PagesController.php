<?php
namespace App\Controller;

use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\View\Exception\MissingViewException;


class PagesController extends AppController
{
    public function display()
    {
        $this->layout = $this->Configuration->getKey('layout');

        $passwd = explode('?', $_SERVER['REQUEST_URI']); // on récupére l'url
        if (isset($passwd[1])) { // si il y a un truc en plus
            $passwd = explode('_', $passwd[1]);
            if (isset($passwd[0]) and $passwd[0] == "resetpasswd") { // si c'est pour reset le password
                if (!empty($passwd[1])) {
                    $this->Lostpassword = TableRegistry::getTableLocator()->get('Lostpassword');
                    $search = $this->Lostpassword->find('all', conditions: ['key' => $passwd[1]])->first(); // on cherche la key de reset password
                    if (!empty($search)) { // si elle existe
                        if (strtotime(date('Y-m-d H:i:s', strtotime($search['created'])) . ' +1 hour') >= time()) { // si le lien ne date pas de plus d'1 heure
                            $resetpsswd['email'] = $search['email'];
                            $resetpsswd['key'] = $search['key'];
                            $this->set(compact('resetpsswd'));
                        }
                    }
                }
            }
        }

        // on delete tout les liens de reset de password au dessus de 1 heure
        $this->Lostpassword = TableRegistry::getTableLocator()->get('Lostpassword');
        $search_passwd = $this->Lostpassword->find();
        foreach ($search_passwd as $key => $value) {
            if (strtotime(date('Y-m-d H:i:s', strtotime($value['created'])) . ' +1 hour') < time()) {
                $this->Lostpassword->delete($value['id']);
            }
        }

        $path = func_get_args();

        $count = count($path);
        if (!$count) {
            return $this->redirect('/');
        }
        $page = $subpage = null;

        if (!empty($path[0])) {
            $page = $path[0];
        }
        if (!empty($path[1])) {
            $subpage = $path[1];
        }

        $title_for_layout = $this->Lang->get('GLOBAL__HOME');
        $this->set(compact('page', 'subpage', 'title_for_layout'));

        try {
            $this->render(implode('/', $path));
        } catch (MissingViewException $e) {
            if (Configure::read('debug')) {
                throw $e;
            }
            throw new NotFoundException();
        }

        // Page principal

        // récupérage des news
        $this->News = TableRegistry::getTableLocator()->get('News'); // on charge le model
        $search_news = $this->News->find('all', recursive: 1, limit: 6, order: 'id desc', conditions: ['published' => 1])->toArray(); // on cherche les 3 dernières news (les plus veille)

        // je cherche toutes les news que l'utilisateur connecté a aimé
        foreach ($search_news as $key => $model) {
            if ($this->isConnected) {
                foreach ($model['likes'] as $value) {
                    foreach ($value as $v) {
                        if ($this->User->getKey('id') == $v) {
                            $search_news[$key]['liked'] = true;
                        }
                    }
                }
            }
            if (!isset($search_news[$key]['liked'])) {
                $search_news[$key]['liked'] = false;
            }

            $search_news[$key]['count_comments'] = count($search_news[$key]['comment']);
            $search_news[$key]['count_likes'] = count($search_news[$key]['likes']);
        }

        $can_like = (bool)$this->Permissions->can('LIKE_NEWS');

        $this->set(compact('search_news', 'can_like')); // on envoie les données à la vue

        //récupération des slides
        $this->Slider = TableRegistry::getTableLocator()->get('Slider');
        $search_slider = $this->Slider->find()->toArray();
        $this->set(compact('search_slider'));

        // Fin
        $this->render('home');
    }

    public function robots()
    {
        $this->disableAutoRender();
        echo file_get_contents(ROOT . DS . 'robots.txt');
    }

    public function index($slug = false)
    {
        if ($slug) {
            $this->Page = TableRegistry::getTableLocator()->get('Page');
            $page = $this->Page->find('all', conditions: ['slug' => $slug])->first();
            if (!empty($page)) {
                $this->layout = $this->Configuration->getKey('layout');

                // Parser variables

                $page['author'] = $this->User->getFromUser('pseudo', $page['user_id']);

                $page['content'] = str_replace('{username}', $this->User->getKey('pseudo'), $page['content']);

                // Parser les conditions

                $count = mb_substr_count($page['content'], '{%') / 2; // on regarde combien de fois il y a une condition (divise par 2 car {% endif %})

                $i = 0;
                while ($i < $count) { // on fais une boucle pour les conditions
                    $i++;

                    $start = explode('{% if(', $page['content']); // on récupère le contenu de la condition
                    $content = explode(') %}', $start[1]);
                    $end = explode('{% endif %}', $content[1]); // et ce qu'on doit afficher pour condition

                    $content_if = $content[0];

                    ob_start();
                    if ($this->isConnected) {
                        $connected = 1;
                    } else {
                        $connected = 0;
                    }
                    if ($this->Server->online()) {
                        $server_online = 1;
                    } else {
                        $server_online = 0;
                    }
                    $content_if = str_replace('{isConnected}', $connected, $content_if);
                    $content_if = str_replace('{isServerOnline}', $server_online, $content_if);

                    if (explode(' == ', $content_if)) {

                        $content_if = explode(' == ', $content_if);

                        if ($content_if[0] == $content_if[1]) { // si la condition s'effectue
                            echo $end[0];
                        }

                    } else {

                        if ($content_if) { // si la condition s'effectue
                            echo $end[0];
                        }

                    }
                    $if_result = ob_get_clean();
                    $page['content'] = str_replace('{% if(' . $content[0] . ') %}' . $end[0] . '{% endif %}', $if_result, $page['content']);
                }
                //

                $this->set(compact('page'));
                $this->set('title_for_layout', $page['title']);
            } else {
                throw new NotFoundException();
            }
        } else {
            throw new NotFoundException();
        }
    }

    public function themeAsset($path = false)
    {
        if (!$path)
            throw new NotFoundException();

        Log::debug($path);
        return $this->response->withFile(ROOT . DS . 'templates' . DS . 'Themed' . $path);
    }
}
