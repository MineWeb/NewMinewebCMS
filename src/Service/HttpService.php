<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Http\Client;

final class HttpService
{
    private Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client([
            'timeout' => 30,
            'ssl_verify_peer' => true,
            'ssl_verify_host' => true,
        ]);
    }

    public function get(string $url, array $options = []): array
    {
        $headers = (array)($options['headers'] ?? []);
        $response = $this->client->get($url, [], ['headers' => $headers]);

        return [
            'status' => $response->getStatusCode(),
            'body' => (string)$response->getStringBody(),
            'headers' => $response->getHeaders(),
        ];
    }

    public function sendGetRequest(string $url): string
    {
        $res = $this->get($url);

        return (string)($res['body'] ?? '');
    }

    public function sendMultipleGetRequests(array|string $urls): array
    {
        $list = is_array($urls) ? $urls : [$urls];
        $out = [];

        foreach ($list as $url) {
            $url = (string)$url;
            if ($url === '') {
                $out[] = '';
                continue;
            }
            $out[] = $this->sendGetRequest($url);
        }

        return $out;
    }
}
