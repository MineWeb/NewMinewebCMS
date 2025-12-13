<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

/**
 * @property \App\Model\Table\UsersTable $User
 * @property \App\Model\Table\NewsTable $News
 * @property \App\Model\Table\CommentsTable $Comment
 * @property \App\Model\Table\LikesTable $Like
 *
 * @property \App\Controller\Component\AuthComponent $Auth
 */
class NewsController extends AppController
{
    public function blog(): Response
    {
        $search_news = $this->getNews();

        $userId = null;
        if ($this->Auth->isConnected()) {
            $identity = $this->Auth->identity();
            if (is_object($identity) && method_exists($identity, 'get')) {
                $id = $identity->get('id');
                if (is_numeric($id)) {
                    $userId = (int)$id;
                }
            }
        }

        if ($userId !== null) {
            foreach ($search_news as $i => $val) {
                if (!isset($val['likes'])) {
                    continue;
                }

                foreach ($val['likes'] as $value) {
                    foreach ($value as $v) {
                        if ((int)$v === $userId) {
                            $search_news[$i]['liked'] = true;
                            break 2;
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

        $can_like = $this->Auth->can('LIKE_NEWS');

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
            order: ['News.id' => 'DESC'],
            conditions: ['News.slug' => $slug],
        )->first();

        if (!$news) {
            throw new NotFoundException();
        }

        $userId = null;
        if ($this->Auth->isConnected()) {
            $identity = $this->Auth->identity();
            if (is_object($identity) && method_exists($identity, 'get')) {
                $id = $identity->get('id');
                if (is_numeric($id)) {
                    $userId = (int)$id;
                }
            }
        }

        if ($userId !== null && isset($news['likes'])) {
            foreach ($news['likes'] as $value) {
                foreach ($value as $v) {
                    if ((int)$v === $userId) {
                        $news['liked'] = true;
                        break 2;
                    }
                }
            }
        }

        if (!isset($news['liked'])) {
            $news['liked'] = false;
        }

        $this->set('title_for_layout', $news['title']);

        $search_news = $this->News->find(
            'all',
            limit: 4,
            order: ['News.id' => 'DESC'],
            conditions: ['News.published' => 1],
        )->all();

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
                'status' => false,
                'messages' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        if (!$this->Auth->can('COMMENT_NEWS')) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('USER__ERROR_MUST_BE_LOGGED'),
            ]));
        }

        $content = (string)$this->getRequest()->getData('content', '');
        $newsId = (int)$this->getRequest()->getData('news_id', 0);

        if ($content === '' || $newsId <= 0) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $identity = $this->Auth->identity();

        $event = new Event('beforeAddComment', $this, [
            'content' => $content,
            'news_id' => $newsId,
            'user' => $identity,
        ]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }

            return $this->response->withStringBody(json_encode($result));
        }

        $userId = null;
        if (is_object($identity) && method_exists($identity, 'get')) {
            $id = $identity->get('id');
            if (is_numeric($id)) {
                $userId = (int)$id;
            }
        }

        if ($userId === null) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('USER__ERROR_MUST_BE_LOGGED'),
            ]));
        }

        $this->Comment = TableRegistry::getTableLocator()->get('Comments');
        $comment = $this->Comment->newEntity([
            'content' => $content,
            'user_id' => $userId,
            'news_id' => $newsId,
        ]);
        $this->Comment->save($comment);

        return $this->response->withStringBody(json_encode([
            'status' => true,
            'messages' => 'success',
        ]));
    }

    public function like(): Response
    {
        $this->disableAutoRender();

        if (!$this->request->is('post')) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'status' => false,
                    'messages' => __('ERROR__BAD_REQUEST'),
                ]));
        }

        if (!$this->Auth->can('LIKE_NEWS')) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'status' => false,
                    'messages' => __('USER__ERROR_MUST_BE_LOGGED'),
                ]));
        }

        $identity = $this->Auth->identity();
        $userId = null;
        if (is_object($identity) && method_exists($identity, 'get')) {
            $id = $identity->get('id');
            if (is_numeric($id)) {
                $userId = (int)$id;
            }
        }

        if ($userId === null) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'status' => false,
                    'messages' => __('USER__ERROR_MUST_BE_LOGGED'),
                ]));
        }

        $newsId = (int)$this->getRequest()->getData('id', 0);
        if ($newsId <= 0) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'status' => false,
                    'messages' => __('ERROR__BAD_REQUEST'),
                ]));
        }

        $this->Like = TableRegistry::getTableLocator()->get('Likes');
        $already = $this->Like->find(
            'all',
            conditions: [
                'news_id' => $newsId,
                'user_id' => $userId,
            ],
        )->first();

        if (!empty($already)) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'status' => false,
                    'messages' => __('ERROR__INTERNAL_ERROR'),
                ]));
        }

        $event = new Event('beforeLike', $this, [
            'news_id' => $newsId,
            'user' => $identity,
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
            'news_id' => $newsId,
            'user_id' => $userId,
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
                    'status' => false,
                    'messages' => __('ERROR__BAD_REQUEST'),
                ]));
        }

        if (!$this->Auth->can('LIKE_NEWS')) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'status' => false,
                    'messages' => __('USER__ERROR_MUST_BE_LOGGED'),
                ]));
        }

        $identity = $this->Auth->identity();
        $userId = null;
        if (is_object($identity) && method_exists($identity, 'get')) {
            $id = $identity->get('id');
            if (is_numeric($id)) {
                $userId = (int)$id;
            }
        }

        if ($userId === null) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'status' => false,
                    'messages' => __('USER__ERROR_MUST_BE_LOGGED'),
                ]));
        }

        $newsId = (int)$this->getRequest()->getData('id', 0);
        if ($newsId <= 0) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'status' => false,
                    'messages' => __('ERROR__BAD_REQUEST'),
                ]));
        }

        $this->Like = TableRegistry::getTableLocator()->get('Likes');
        $already = $this->Like->find(
            'all',
            conditions: [
                'news_id' => $newsId,
                'user_id' => $userId,
            ],
        )->first();

        if (empty($already)) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'status' => false,
                    'messages' => __('ERROR__INTERNAL_ERROR'),
                ]));
        }

        $event = new Event('beforeDislike', $this, [
            'news_id' => $newsId,
            'user' => $identity,
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

        $this->Comment = TableRegistry::getTableLocator()->get('Comments');
        $search = $this->Comment->find(
            'all',
            conditions: ['id' => $this->getRequest()->getData('id')],
        )->first();

        if (!$search) {
            return $this->response->withStringBody('NOT_FOUND');
        }

        $identity = $this->Auth->identity();
        $username = null;

        if (is_object($identity) && method_exists($identity, 'get')) {
            $p = $identity->get('username');
            if (is_string($p)) {
                $username = $p;
            }
        }

        $canDeleteAny = $this->Auth->can('DELETE_COMMENT');
        $canDeleteOwn = $this->Auth->can('DELETE_HIS_COMMENT') && $username !== null && $username === ($search['author'] ?? null);

        if (!$canDeleteAny && !$canDeleteOwn) {
            return $this->response->withStringBody('NOT_ADMIN');
        }

        if (!$this->request->is('post')) {
            return $this->response->withStringBody('NOT_POST');
        }

        $event = new Event('beforeDeleteComment', $this, [
            'comment_id' => $this->getRequest()->getData('id'),
            'news_id' => $search['news_id'] ?? null,
            'user' => $identity,
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

    public function getNews(): array
    {
        $this->News = TableRegistry::getTableLocator()->get('News');
        $search_news = $this->News->find(
            'all',
            recursive: 1,
            order: ['News.id' => 'DESC'],
            conditions: ['News.published' => 1],
        )->toArray();

        foreach ($search_news as $key => $model) {
            $search_news[$key]['absolute_url'] = Router::url('/blog/' . $model['slug'], true);
        }

        return $search_news;
    }
}
