<?php
declare(strict_types=1);

namespace App\Service;

class HttpService
{
    public function sendGetRequest(string $url): string
    {
        $ch = curl_init();
        if ($ch === false) {
            return '';
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => ['User-Agent: MineWebCMS'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $result = curl_exec($ch);
        curl_close($ch);

        return is_string($result) ? $result : '';
    }

    public function sendMultipleGetRequests(array|string $urls): array
    {
        if (!is_array($urls)) {
            $urls = [$urls];
        }

        $multi = curl_multi_init();

        $channels = [];
        $results = [];

        foreach ($urls as $url) {
            if (!is_string($url) || $url === '') {
                continue;
            }

            $ch = curl_init();
            if ($ch === false) {
                continue;
            }

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => ['User-Agent: MineWebCMS'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            curl_multi_add_handle($multi, $ch);
            $channels[] = $ch;
        }

        $active = 0;

        do {
            $status = curl_multi_exec($multi, $active);
        } while ($status === CURLM_CALL_MULTI_PERFORM);

        while ($active && $status === CURLM_OK) {
            if (curl_multi_select($multi) === -1) {
                usleep(100);
                continue;
            }

            do {
                $status = curl_multi_exec($multi, $active);
            } while ($status === CURLM_CALL_MULTI_PERFORM);
        }

        foreach ($channels as $ch) {
            $content = curl_multi_getcontent($ch);
            $results[] = is_string($content) ? $content : '';
            curl_multi_remove_handle($multi, $ch);
        }

        curl_multi_close($multi);

        return $results;
    }
}
