<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Log\Log;
use Cake\View\Exception\MissingViewException;

/**
 * @property \App\Model\Table\ConfigurationsTable $Configuration
 * @property \App\Model\Table\UsersTable $User
 * @property \App\Model\Table\ServersTable $Server
 *
 * @property \App\Controller\Component\AuthComponent $Auth
 */
class PagesController extends AppController
{
    public function display(string ...$path): Response
    {
        $this->viewBuilder()->setLayout((string)$this->config->get('layout'));

        $requestUri = (string)$this->getRequest()->getEnv('REQUEST_URI');
        $parts = explode('?', $requestUri);

        if (isset($parts[1])) {
            $query = explode('_', $parts[1]);
            if (($query[0] ?? null) === 'resetpasswd' && !empty($query[1])) {
                $LostpasswordsTable = $this->fetchTable('Lostpasswords');

                $search = $LostpasswordsTable
                    ->find()
                    ->where(['key' => $query[1]])
                    ->first();

                if ($search !== null) {
                    $createdAt = $search->get('created_at');
                    if ($createdAt && $createdAt->addHour()->isFuture()) {
                        $resetpsswd = [
                            'email' => $search->get('email'),
                            'key' => $search->get('key'),
                        ];
                        $this->set(compact('resetpsswd'));
                    }
                }
            }
        }

        $LostpasswordsTable = $this->fetchTable('Lostpasswords');
        $searchPasswd = $LostpasswordsTable->find()->all();

        foreach ($searchPasswd as $value) {
            $createdAt = $value->get('created_at');
            if ($createdAt && $createdAt->addHour()->isPast()) {
                $LostpasswordsTable->delete($value);
            }
        }

        if (count($path) === 0) {
            return $this->redirect('/');
        }

        $page = $path[0] ?? null;
        $subpage = $path[1] ?? null;

        $title_for_layout = __('GLOBAL__HOME');
        $this->set(compact('page', 'subpage', 'title_for_layout'));

        if ($page !== 'home') {
            try {
                $this->viewBuilder()
                    ->setTemplatePath('Pages')
                    ->setTemplate(implode('/', $path));

                return $this->render();
            } catch (MissingViewException $e) {
                if (Configure::read('debug')) {
                    throw $e;
                }
                throw new NotFoundException();
            }
        }

        $newsTable = $this->fetchTable('News');

        $search_news = $newsTable
            ->find()
            ->contain(['Comments', 'Likes'])
            ->where(['News.published' => 1])
            ->orderBy(['News.id' => 'DESC'])
            ->limit(6)
            ->toArray();

        $identity = $this->Auth->identity();
        $userId = null;

        if ($this->Auth->isConnected() && is_object($identity) && method_exists($identity, 'get')) {
            $id = $identity->get('id');
            if (is_numeric($id)) {
                $userId = (int)$id;
            }
        }

        foreach ($search_news as $key => $model) {
            if ($userId !== null && is_iterable($model->likes ?? null)) {
                foreach ($model->likes as $like) {
                    if ((int)($like->user_id ?? 0) === $userId) {
                        $search_news[$key]['liked'] = true;
                        break;
                    }
                }
            }

            if (!isset($search_news[$key]['liked'])) {
                $search_news[$key]['liked'] = false;
            }

            $search_news[$key]['count_comments'] = is_iterable($model->comments ?? null)
                ? count($model->comments)
                : 0;

            $search_news[$key]['count_likes'] = is_iterable($model->likes ?? null)
                ? count($model->likes)
                : 0;
        }

        $can_like = (bool)$this->Auth->can('LIKE_NEWS');
        $this->set(compact('search_news', 'can_like'));

        $sliderTable = $this->fetchTable('Sliders');
        $search_slider = $sliderTable->find()->toArray();
        $this->set(compact('search_slider'));

        $this->viewBuilder()
            ->setTemplatePath('Pages')
            ->setTemplate('home');

        return $this->render();
    }

    public function robots(): Response
    {
        $this->disableAutoRender();

        $file = ROOT . DIRECTORY_SEPARATOR . 'robots.txt';
        $content = is_file($file) ? (string)file_get_contents($file) : '';

        return $this->response
            ->withType('text/plain')
            ->withStringBody($content);
    }

    public function index(string $slug): Response
    {
        if ($slug === '') {
            throw new NotFoundException();
        }

        $pageTable = $this->fetchTable('Pages');

        $page = $pageTable
            ->find()
            ->where(['slug' => $slug])
            ->first();

        if ($page === null) {
            throw new NotFoundException();
        }

        $this->viewBuilder()->setLayout((string)$this->config->get('layout'));

        $page['author'] = $this->User->getFromUser('username', (int)$page['user_id']);

        $username = '';
        if ($this->Auth->isConnected()) {
            $identity = $this->Auth->identity();
            if (is_object($identity) && method_exists($identity, 'get')) {
                $p = $identity->get('username');
                if (is_string($p)) {
                    $username = $p;
                }
            }
        }

        $page['content'] = str_replace('{USERNAME}', $username, (string)$page['content']);

        $count = (int)(mb_substr_count($page['content'], '{%') / 2);

        $i = 0;
        while ($i < $count) {
            $i++;

            $start = explode('{% if(', $page['content'], 2);
            if (count($start) < 2) {
                break;
            }

            $contentParts = explode(') %}', $start[1], 2);
            if (count($contentParts) < 2) {
                break;
            }

            $conditionRaw = $contentParts[0];
            $endParts = explode('{% endif %}', $contentParts[1], 2);
            if (count($endParts) < 2) {
                break;
            }

            $blockContent = $endParts[0];

            $connected = $this->Auth->isConnected() ? 1 : 0;
            $server_online = $this->Server->online() ? 1 : 0;

            $condition = str_replace(
                ['{isConnected}', '{isServerOnline}'],
                [(string)$connected, (string)$server_online],
                $conditionRaw
            );

            $result = '';

            if (strpos($condition, ' == ') !== false) {
                $parts = explode(' == ', $condition, 2);
                if (isset($parts[0], $parts[1]) && $parts[0] === $parts[1]) {
                    $result = $blockContent;
                }
            } else {
                if ($condition) {
                    $result = $blockContent;
                }
            }

            $search = '{% if(' . $conditionRaw . ') %}' . $blockContent . '{% endif %}';
            $page['content'] = str_replace($search, $result, $page['content']);
        }

        $this->set(compact('page'));
        $this->set('title_for_layout', $page['title']);

        $this->viewBuilder()
            ->setTemplatePath('Pages')
            ->setTemplate('index');

        return $this->render();
    }

    public function themeAsset(string $path = ''): Response
    {
        if ($path === '') {
            throw new NotFoundException();
        }

        $this->disableAutoRender();

        Log::debug($path);

        $normalized = str_replace(['..', '\\'], ['', '/'], $path);
        $normalized = ltrim($normalized, '/');

        $filePath = ROOT
            . DIRECTORY_SEPARATOR . 'templates'
            . DIRECTORY_SEPARATOR . 'Themed'
            . DIRECTORY_SEPARATOR . $normalized;

        if (!is_file($filePath)) {
            throw new NotFoundException();
        }

        return $this->response->withFile($filePath);
    }
}
