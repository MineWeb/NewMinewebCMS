<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

class NewsController extends AppController
{
    public function blog(): Response
    {
        $search_news = $this->getNews();

        if ($this->isConnected) {
            foreach ($search_news as $i => $val) {
                if (!isset($val['likes'])) {
                    continue;
                }

                foreach ($val['likes'] as $value) {
                    foreach ($value as $v) {
                        if ($this->User->getKey('id') === $v) {
                            $search_news[$i]['liked'] = true;
                        }
                    }
                }
            }
        }

        foreach ($search_news as $i => $val) {
            if (!isset($val['liked'])) {
                $search_news[$i]['liked'] = false;
            }
        }

        $can_like = $this->Permissions->can('LIKE_NEWS');

        $this->set('title_for_layout', __('NEWS__TITLE'));
        $this->set(compact('search_news', 'can_like'));

        $this->viewBuilder()
            ->setTemplatePath('News')
            ->setTemplate('blog');

        return $this->render();
    }

    public function api(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $search_news = $this->getNews();

        return $this->response->withStringBody(json_encode($search_news));
    }

    public function index(string $slug): Response
    {
        if ($slug === '') {
            throw new NotFoundException();
        }

        $this->News = TableRegistry::getTableLocator()->get('News');

        $news = $this->News->find(
            'all',
            recursive: 1,
            order: 'id desc',
            conditions: ['slug' => $slug]
        )->first();

        if (!$news) {
            throw new NotFoundException();
        }

        if ($this->isConnected && isset($news['likes'])) {
            foreach ($news['likes'] as $value) {
                foreach ($value as $v) {
                    if ($this->User->getKey('id') === $v) {
                        $news['liked'] = true;
                    }
                }
            }
        }

        if (!isset($news['liked'])) {
            $news['liked'] = false;
        }

        $this->set('title_for_layout', $news['title']);

        $search_news = $this->News->find('all', [
            'limit' => 4,
            'order' => 'id desc',
            'conditions' => ['published' => 1],
        ])->all();

        $this->set(compact('search_news', 'news'));

        $this->viewBuilder()
            ->setTemplatePath('News')
            ->setTemplate('index');

        return $this->render();
    }

    public function addComment(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->request->is('post')) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        if (!$this->Permissions->can('COMMENT_NEWS')) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('USER__ERROR_MUST_BE_LOGGED'),
            ]));
        }

        if (empty($this->getRequest()->getData('content')) || empty($this->getRequest()->getData('news_id'))) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $event = new Event('beforeAddComment', $this, [
            'content' => $this->getRequest()->getData('content'),
            'news_id' => $this->getRequest()->getData('news_id'),
            'user' => $this->User->getAllFromCurrentUser(),
        ]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }

            return $this->response->withStringBody(json_encode($result));
        }

        $this->Comment = TableRegistry::getTableLocator()->get('Comment');
        $comment = $this->Comment->newEntity([
            'content' => $this->getRequest()->getData('content'),
            'user_id' => $this->User->getKey('id'),
            'news_id' => (int)$this->getRequest()->getData('news_id'),
        ]);
        $this->Comment->save($comment);

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => 'success',
        ]));
    }

    public function like(): Response
    {
        $this->disableAutoRender();

        if (!$this->request->is('post')) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'statut' => false,
                    'msg' => __('ERROR__BAD_REQUEST'),
                ]));
        }

        if (!$this->Permissions->can('LIKE_NEWS')) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'statut' => false,
                    'msg' => __('USER__ERROR_MUST_BE_LOGGED'),
                ]));
        }

        $this->Like = TableRegistry::getTableLocator()->get('Likes');
        $already = $this->Like->find(
            'all',
            conditions: [
                'news_id' => $this->getRequest()->getData('id'),
                'user_id' => $this->User->getKey('id'),
            ]
        )->first();

        if (!empty($already)) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'statut' => false,
                    'msg' => __('ERROR__INTERNAL_ERROR'),
                ]));
        }

        $event = new Event('beforeLike', $this, [
            'news_id' => $this->getRequest()->getData('id'),
            'user' => $this->User->getAllFromCurrentUser(),
        ]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }

            return $this->response->withType('application/json')
                ->withStringBody(json_encode($result));
        }

        $like = $this->Like->newEntity([
            'news_id' => $this->getRequest()->getData('id'),
            'user_id' => $this->User->getKey('id'),
        ]);
        $this->Like->save($like);

        return $this->response;
    }

    public function dislike(): Response
    {
        $this->disableAutoRender();

        if (!$this->request->is('post')) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'statut' => false,
                    'msg' => __('ERROR__BAD_REQUEST'),
                ]));
        }

        if (!$this->Permissions->can('LIKE_NEWS')) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'statut' => false,
                    'msg' => __('USER__ERROR_MUST_BE_LOGGED'),
                ]));
        }

        $this->Like = TableRegistry::getTableLocator()->get('Likes');
        $already = $this->Like->find(
            'all',
            conditions: [
                'news_id' => $this->getRequest()->getData('id'),
                'user_id' => $this->User->getKey('id'),
            ]
        )->first();

        if (empty($already)) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'statut' => false,
                    'msg' => __('ERROR__INTERNAL_ERROR'),
                ]));
        }

        $event = new Event('beforeDislike', $this, [
            'news_id' => $this->getRequest()->getData('id'),
            'user' => $this->User->getAllFromCurrentUser(),
        ]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }

            return $this->response->withType('application/json')
                ->withStringBody(json_encode($result));
        }

        $this->Like->delete($already);

        return $this->response;
    }

    public function ajaxCommentDelete(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('text/plain');

        $this->Comment = TableRegistry::getTableLocator()->get('Comment');
        $search = $this->Comment->find(
            'all',
            conditions: ['id' => $this->getRequest()->getData('id')]
        )->first();

        if (
            !$this->Permissions->can('DELETE_COMMENT') &&
            !(
                $this->Permissions->can('DELETE_HIS_COMMENT') &&
                $this->User->getKey('pseudo') === ($search['author'] ?? null)
            )
        ) {
            return $this->response->withStringBody('NOT_ADMIN');
        }

        if (!$this->request->is('post')) {
            return $this->response->withStringBody('NOT_POST');
        }

        $event = new Event('beforeDeleteComment', $this, [
            'comment_id' => $this->getRequest()->getData('id'),
            'news_id' => $search['news_id'] ?? null,
            'user' => $this->User->getAllFromCurrentUser(),
        ]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }

            return $this->response->withStringBody((string)$result);
        }

        $this->Comment->delete($search);

        return $this->response->withStringBody('true');
    }

    /**
     * @return array
     */
    public function getNews(): array
    {
        $this->News = TableRegistry::getTableLocator()->get('News');
        $search_news = $this->News->find(
            'all',
            recursive: 1,
            order: 'id desc',
            conditions: ['published' => 1]
        )->toArray();

        foreach ($search_news as $key => $model) {
            $search_news[$key]['absolute_url'] = Router::url('/blog/' . $model['slug'], true);
        }

        return $search_news;
    }
}
