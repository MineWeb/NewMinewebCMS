<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Routing\Router;

class NewsController extends AppController
{
    use LocatorAwareTrait;

    public function blog(): Response
    {
        $userId = $this->getConnectedUserId();

        $News = $this->fetchTable('News');

        $search_news = $News->find()
            ->where(['News.published' => 1])
            ->orderBy(['News.id' => 'DESC'])
            ->contain([
                'Likes' => function ($q) use ($userId) {
                    if (!$userId) {
                        return $q->where(['Likes.id IS' => null]);
                    }

                    return $q->select(['id', 'news_id', 'user_id'])
                        ->where(['Likes.user_id' => $userId]);
                },
            ])
            ->all()
            ->toArray();

        foreach ($search_news as $k => $n) {
            $search_news[$k]['liked'] = !empty($n['likes']);
            $search_news[$k]['absolute_url'] = Router::url('/blog/' . ($n['slug'] ?? ''), true);
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

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($this->getNews(), JSON_UNESCAPED_UNICODE));
    }

    public function index(): Response
    {
        $slug = (string)($this->request->getParam('pass.0') ?? '');
        if ($slug === '') {
            throw new NotFoundException();
        }

        $userId = $this->getConnectedUserId();

        $News = $this->fetchTable('News');

        $news = $News->find()
            ->where(['News.slug' => $slug])
            ->contain([
                'Comments',
                'Likes' => function ($q) use ($userId) {
                    if (!$userId) {
                        return $q->where(['Likes.id IS' => null]);
                    }

                    return $q->select(['id', 'news_id', 'user_id'])
                        ->where(['Likes.user_id' => $userId]);
                },
            ])
            ->orderBy(['News.id' => 'DESC'])
            ->first();

        if (!$news) {
            throw new NotFoundException();
        }

        $news['liked'] = !empty($news['likes']);

        $this->set('title_for_layout', (string)($news['title'] ?? __('NEWS__TITLE')));

        $search_news = $News->find()
            ->where(['News.published' => 1])
            ->select(['id', 'slug', 'title', 'updated_at', 'created_at', 'published'])
            ->orderBy(['News.id' => 'DESC'])
            ->limit(4)
            ->all()
            ->toArray();

        foreach ($search_news as $k => $model) {
            $search_news[$k]['absolute_url'] = Router::url('/blog/' . ($model['slug'] ?? ''), true);
        }

        $this->set(compact('search_news', 'news'));

        $this->viewBuilder()
            ->setTemplatePath('News')
            ->setTemplate('index');

        return $this->render();
    }

    public function addComment(): Response
    {
        $this->disableAutoRender();

        if (!$this->request->is('post')) {
            return $this->json(false, (string)__('ERROR__BAD_REQUEST'));
        }

        if (!$this->Auth->can('COMMENT_NEWS')) {
            return $this->json(false, (string)__('USER__ERROR_MUST_BE_LOGGED'));
        }

        $content = (string)$this->request->getData('content', '');
        $newsId = (int)$this->request->getData('news_id', 0);

        if ($content === '' || $newsId <= 0) {
            return $this->json(false, (string)__('ERROR__FILL_ALL_FIELDS'));
        }

        $identity = $this->Auth->identity();
        $userId = $this->getConnectedUserId();
        if ($userId === null) {
            return $this->json(false, (string)__('USER__ERROR_MUST_BE_LOGGED'));
        }

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
            if (is_array($result)) {
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode($result, JSON_UNESCAPED_UNICODE));
            }

            return $this->json(false, (string)__('ERROR__INTERNAL_ERROR'));
        }

        $Comments = $this->fetchTable('Comments');
        $comment = $Comments->newEntity([
            'content' => $content,
            'user_id' => $userId,
            'news_id' => $newsId,
        ]);

        if (!$Comments->save($comment)) {
            return $this->json(false, (string)__('ERROR__INTERNAL_ERROR'));
        }

        return $this->json(true, (string)__('GLOBAL__SUCCESS'));
    }

    public function like(): Response
    {
        $this->disableAutoRender();

        if (!$this->request->is('post')) {
            return $this->json(false, (string)__('ERROR__BAD_REQUEST'));
        }

        if (!$this->Auth->can('LIKE_NEWS')) {
            return $this->json(false, (string)__('USER__ERROR_MUST_BE_LOGGED'));
        }

        $userId = $this->getConnectedUserId();
        if ($userId === null) {
            return $this->json(false, (string)__('USER__ERROR_MUST_BE_LOGGED'));
        }

        $newsId = (int)$this->request->getData('id', 0);
        if ($newsId <= 0) {
            return $this->json(false, (string)__('ERROR__BAD_REQUEST'));
        }

        $Likes = $this->fetchTable('Likes');

        $already = $Likes->find()
            ->where(['news_id' => $newsId, 'user_id' => $userId])
            ->first();

        if ($already) {
            return $this->json(false, (string)__('ERROR__INTERNAL_ERROR'));
        }

        $identity = $this->Auth->identity();
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
            if (is_array($result)) {
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode($result, JSON_UNESCAPED_UNICODE));
            }

            return $this->json(false, (string)__('ERROR__INTERNAL_ERROR'));
        }

        $like = $Likes->newEntity([
            'news_id' => $newsId,
            'user_id' => $userId,
        ]);

        if (!$Likes->save($like)) {
            return $this->json(false, (string)__('ERROR__INTERNAL_ERROR'));
        }

        return $this->json(true, (string)__('GLOBAL__SUCCESS'));
    }

    public function dislike(): Response
    {
        $this->disableAutoRender();

        if (!$this->request->is('post')) {
            return $this->json(false, (string)__('ERROR__BAD_REQUEST'));
        }

        if (!$this->Auth->can('LIKE_NEWS')) {
            return $this->json(false, (string)__('USER__ERROR_MUST_BE_LOGGED'));
        }

        $userId = $this->getConnectedUserId();
        if ($userId === null) {
            return $this->json(false, (string)__('USER__ERROR_MUST_BE_LOGGED'));
        }

        $newsId = (int)$this->request->getData('id', 0);
        if ($newsId <= 0) {
            return $this->json(false, (string)__('ERROR__BAD_REQUEST'));
        }

        $Likes = $this->fetchTable('Likes');

        $already = $Likes->find()
            ->where(['news_id' => $newsId, 'user_id' => $userId])
            ->first();

        if (!$already) {
            return $this->json(false, (string)__('ERROR__INTERNAL_ERROR'));
        }

        $identity = $this->Auth->identity();
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
            if (is_array($result)) {
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode($result, JSON_UNESCAPED_UNICODE));
            }

            return $this->json(false, (string)__('ERROR__INTERNAL_ERROR'));
        }

        if (!$Likes->delete($already)) {
            return $this->json(false, (string)__('ERROR__INTERNAL_ERROR'));
        }

        return $this->json(true, (string)__('GLOBAL__SUCCESS'));
    }

    public function ajaxCommentDelete(): Response
    {
        $this->disableAutoRender();

        if (!$this->request->is('post')) {
            return $this->response->withType('text/plain')->withStringBody('NOT_POST');
        }

        $Comments = $this->fetchTable('Comments');

        $id = (int)$this->request->getData('id', 0);
        if ($id <= 0) {
            return $this->response->withType('text/plain')->withStringBody('NOT_FOUND');
        }

        $search = $Comments->find()->where(['id' => $id])->first();
        if (!$search) {
            return $this->response->withType('text/plain')->withStringBody('NOT_FOUND');
        }

        $identity = $this->Auth->identity();
        $username = null;

        if (is_object($identity) && method_exists($identity, 'get')) {
            $p = $identity->get('username');
            if (is_string($p)) {
                $username = $p;
            }
        }

        $author = $search['author'] ?? null;
        $canDeleteAny = $this->Auth->can('DELETE_COMMENT');
        $canDeleteOwn = $this->Auth->can('DELETE_HIS_COMMENT') && $username !== null && $author !== null && $username === $author;

        if (!$canDeleteAny && !$canDeleteOwn) {
            return $this->response->withType('text/plain')->withStringBody('NOT_ADMIN');
        }

        $event = new Event('beforeDeleteComment', $this, [
            'comment_id' => $id,
            'news_id' => $search['news_id'] ?? null,
            'user' => $identity,
        ]);
        $this->getEventManager()->dispatch($event);

        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }

            return $this->response->withType('text/plain')->withStringBody((string)$result);
        }

        $Comments->delete($search);

        return $this->response->withType('text/plain')->withStringBody('true');
    }

    public function getNews(): array
    {
        $userId = $this->getConnectedUserId();

        $News = $this->fetchTable('News');

        $search_news = $News->find()
            ->where(['News.published' => 1])
            ->orderBy(['News.id' => 'DESC'])
            ->contain([
                'Likes' => function ($q) use ($userId) {
                    if (!$userId) {
                        return $q->where(['Likes.id IS' => null]);
                    }

                    return $q->select(['id', 'news_id', 'user_id'])
                        ->where(['Likes.user_id' => $userId]);
                },
                'Comments',
            ])
            ->all()
            ->toArray();

        foreach ($search_news as $k => $model) {
            $search_news[$k]['liked'] = !empty($model['likes']);
            $search_news[$k]['absolute_url'] = Router::url('/blog/' . ($model['slug'] ?? ''), true);
        }

        return $search_news;
    }

    private function getConnectedUserId(): ?int
    {
        if (!$this->Auth->isConnected()) {
            return null;
        }

        $id = $this->Auth->id();
        if (!is_numeric($id)) {
            return null;
        }

        return (int)$id;
    }

    private function json(bool $status, string $messages): Response
    {
        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'status' => $status,
                'messages' => $messages,
            ], JSON_UNESCAPED_UNICODE));
    }
}
