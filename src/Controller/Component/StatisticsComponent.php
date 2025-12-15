<?php
declare(strict_types=1);

namespace App\Controller\Component;

use App\Model\Table\VisitsTable;
use Cake\Controller\Component;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\Locator\LocatorAwareTrait;

final class StatisticsComponent extends Component
{
    use LocatorAwareTrait;

    private VisitsTable $Visits;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->Visits = $this->fetchTable('Visits');
    }

    public function startup(EventInterface $event): void
    {
        if (headers_sent()) {
            return;
        }

        $request = $event->getSubject()?->getRequest();
        if (!$request instanceof ServerRequest) {
            return;
        }

        if ($request->getAttribute('exception')) {
            return;
        }

        $session = $request->getSession();
        if ($session->check('visit_check')) {
            return;
        }

        $ip = (string)$request->clientIp();

        $existsToday = $this->Visits
            ->find()
            ->select(['id'])
            ->where([
                'ip' => $ip,
                'created_at LIKE' => date('Y-m-d') . '%',
            ])
            ->first();

        if ($existsToday === null) {
            $referer = $this->serverString('HTTP_REFERER');
            $userAgent = $this->serverString('HTTP_USER_AGENT');
            $languageHeader = $this->serverString('HTTP_ACCEPT_LANGUAGE');

            $language = $languageHeader !== 'null' ? substr($languageHeader, 0, 2) : 'nu';

            $host = $this->serverString('HTTP_HOST');
            $uri = $this->serverString('REQUEST_URI');
            $page = $host !== 'null' && $uri !== 'null'
                ? 'http://' . $host . $uri
                : 'null';

            $visit = $this->Visits->newEntity([
                'ip' => $ip,
                'referer' => $referer,
                'lang' => $language,
                'navigator' => $userAgent,
                'page' => $page,
            ]);

            $this->Visits->save($visit);
        }

        $session->write('visit_check', true);
    }

    private function serverString(string $key): string
    {
        $value = $_SERVER[$key] ?? '';
        $value = is_string($value) ? $value : '';

        if ($value === '') {
            return 'null';
        }

        return htmlentities($value, ENT_QUOTES, 'UTF-8');
    }
}
