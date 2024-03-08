<?php
namespace App\Controller;

use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

class NewsController extends AppController
{
    function blog()
    {
        // récupérage des news
        $search_news = $this->getNews(); // on charge le model

        if ($this->isConnected) {
            $i = 0;
            foreach ($search_news as $val) {
                foreach ($val['likes'] as $value) {
                    foreach ($value as $v) {
                        if ($this->User->getKey('id') == $v) {
                            $search_news[$i]['liked'] = true;
                        }
                    }
                }
                $i++;
            }
        }
        $i = 0;
        foreach ($search_news as $news => $val) {
            if (!isset($news['liked'])) {
                $search_news[$i]['liked'] = false;
            }
            $i++;
        }

        $can_like = $this->Permissions->can('LIKE_NEWS');

        $this->set('title_for_layout', $this->Lang->get('NEWS__TITLE'));
        $this->set(compact('search_news', 'can_like'));
    }

    function api(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        // récupérage des news
        $search_news = $this->getNews();

        return $this->response->withStringBody(json_encode($search_news));
    }

    function index($slug)
    {
        if (isset($slug)) { // si le slug est présent
            $this->News = TableRegistry::getTableLocator()->get('News'); // on charge le model
            $news = $this->News->find('all', recursive: 1, order: 'id desc', conditions: ['slug' => $slug])->first(); // on cherche les 3 dernières news (les plus veille)
            if ($news) { // si le slug existe
                if ($this->isConnected) {
                    foreach ($news['likes'] as $k => $value) {
                        foreach ($value as $column => $v) {
                            if ($this->User->getKey('id') == $v) {
                                $news['liked'] = true;
                            }
                        }
                    }
                }
                if (!isset($news['liked'])) {
                    $news['liked'] = false;
                }

                $this->set('title_for_layout', $news['title']);

                // on chercher les 4 dernières news pour la sidebar
                $search_news = $this->News->find('all', ['limit' => '4', 'order' => 'id desc', 'conditions' => ['published' => 1]])->all(); // on cherche les 3 dernières news (les plus veille)
                $this->set(compact('search_news', 'news')); // on envoie les données à la vue
            } else {
                throw new NotFoundException();
            }
        } else {
            throw new NotFoundException();
        }
    }

    function addComment()
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');
        if ($this->request->is('post')) {
            if ($this->Permissions->can('COMMENT_NEWS')) {
                if (!empty($this->getRequest()->getData('content')) && !empty($this->getRequest()->getData('news_id'))) {
                    $event = new Event('beforeAddComment', $this, ['content' => $this->getRequest()->getData('content'), 'news_id' => $this->getRequest()->getData('news_id'), 'user' => $this->User->getAllFromCurrentUser()]);
                    $this->getEventManager()->dispatch($event);
                    if ($event->isStopped()) {
                        return $event->getResult();
                    }

                    $this->Comment = TableRegistry::getTableLocator()->get('Comment');
                    $comment = $this->Comment->newEntity([
                        'content' => $this->getRequest()->getData('content'),
                        'user_id' => $this->User->getKey('id'),
                        'news_id' => intval($this->getRequest()->getData('news_id'))
                    ]);
                    $this->Comment->save($comment);

                    return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => 'success']));
                } else {
                    return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')]));
                }
            } else {
                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('USER__ERROR_MUST_BE_LOGGED')]));
            }
        } else {
            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__BAD_REQUEST')]));
        }
    }

    function like()
    {
        $this->disableAutoRender();
        if ($this->request->is('post')) {
            if ($this->Permissions->can('LIKE_NEWS')) {
                $this->Like = TableRegistry::getTableLocator()->get('Likes');
                $already = $this->Like->find('all', conditions: ['news_id' => $this->getRequest()->getData('id'), 'user_id' => $this->User->getKey('id')])->first();
                if (empty($already)) {
                    $event = new Event('beforeLike', $this, ['news_id' => $this->getRequest()->getData('id'), 'user' => $this->User->getAllFromCurrentUser()]);
                    $this->getEventManager()->dispatch($event);
                    if ($event->isStopped()) {
                        return $event->getResult();
                    }

                    $like = $this->Like->newEntity(['news_id' => $this->getRequest()->getData('id'), 'user_id' => $this->User->getKey('id')]);
                    $this->Like->save($like);
                    return $this->response;
                } else {
                    return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__INTERNAL_ERROR')]));
                }
            } else {
                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('USER__ERROR_MUST_BE_LOGGED')]));
            }
        } else {
            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__BAD_REQUEST')]));
        }
    }

    function dislike()
    {
        $this->disableAutoRender();
        if ($this->request->is('post')) {
            if ($this->Permissions->can('LIKE_NEWS')) {
                $this->Like = TableRegistry::getTableLocator()->get('Likes');
                $already = $this->Like->find('all', conditions: ['news_id' => $this->getRequest()->getData('id'), 'user_id' => $this->User->getKey('id')])->first();
                if (!empty($already)) {
                    $event = new Event('beforeDislike', $this, ['news_id' => $this->getRequest()->getData('id'), 'user' => $this->User->getAllFromCurrentUser()]);
                    $this->getEventManager()->dispatch($event);
                    if ($event->isStopped()) {
                        return $event->getResult();
                    }

                    $this->Like->delete($already);
                    return $this->response;
                } else {
                    return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__INTERNAL_ERROR')]));
                }
            } else {
                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('USER__ERROR_MUST_BE_LOGGED')]));
            }
        } else {
            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__BAD_REQUEST')]));
        }
    }


    function ajaxCommentDelete()
    {
        $this->disableAutoRender();
        $this->Comment = TableRegistry::getTableLocator()->get('Comment');
        $search = $this->Comment->find('all', conditions: ['id' => $this->getRequest()->getData('id')])->first();
        if ($this->Permissions->can('DELETE_COMMENT') or $this->Permissions->can('DELETE_HIS_COMMENT') and $this->User->getKey('pseudo') == $search['author']) {
            if ($this->request->is('post')) {
                $event = new Event('beforeDeleteComment', $this, ['comment_id' => $this->getRequest()->getData('id'), 'news_id' => $search['news_id'], 'user' => $this->User->getAllFromCurrentUser()]);
                $this->getEventManager()->dispatch($event);
                if ($event->isStopped()) {
                    return $event->getResult();
                }

                $this->Comment->delete($search);
                echo 'true';
            } else {
                echo 'NOT_POST';
            }
        } else {
            echo 'NOT_ADMIN';
        }
    }

    /**
     * @return array|int
     */
    public function getNews(): int|array
    {
        $this->News = TableRegistry::getTableLocator()->get('News'); // on charge le model
        $search_news = $this->News->find('all', recursive: 1, order: 'id desc', conditions: ['published' => 1])->toArray();

        foreach ($search_news as $key => $model) {
            $search_news[$key]['absolute_url'] = Router::url('/blog/' . $model['slug'], true);
        }
        return $search_news;
    }

}
