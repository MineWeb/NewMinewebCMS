<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\Table;
use Cake\Routing\Router;

final class SeoService
{
    private ConfigurationService $configuration;

    public function __construct(?ConfigurationService $configuration = null)
    {
        $this->configuration = $configuration ?? new ConfigurationService();
    }

    public function isSqliteDefaultConnection(): bool
    {
        $conn = ConnectionManager::get('default');
        $driver = strtolower((string)$conn->config()['driver']);

        return str_contains($driver, 'sqlite');
    }

    private function getWebsiteNameFallback(): string
    {
        return $this->configuration->getWebsiteName();
    }

    public function build(
        Table $seoTable,
        string $requestTarget,
        string $titleFallback,
        bool $isSqlite,
    ): array {
        $websiteNameFallback = $this->getWebsiteNameFallback();

        $default = $seoTable->find()
            ->where(['Seo.page IS' => null])
            ->first();

        $defaultTitle = (string)($default?->get('title') ?? '{TITLE} - {WEBSITE_NAME}');
        $defaultDescription = (string)($default?->get('description') ?? '');
        $defaultImg = (string)($default?->get('img_url') ?? '');
        $defaultFavicon = (string)($default?->get('favicon_url') ?? '');
        $defaultThemeColor = (string)($default?->get('theme_color') ?? '');
        $defaultTwitterSite = (string)($default?->get('twitter_site') ?? '');

        $condition = ["'" . $requestTarget . "' LIKE CONCAT(page, '%')"];
        if ($isSqlite) {
            $condition = ["'" . $requestTarget . "' LIKE page || '%'"];
        }

        $matches = $seoTable->find()->where($condition)->toArray();

        $pageRow = [];
        if ($matches) {
            $candidate = max($matches);
            $candidatePage = (string)($candidate['page'] ?? '');

            if ($candidatePage === $requestTarget || $requestTarget !== '/') {
                $pageRow = $candidate;
            }
        }

        $titleTemplate = (string)($pageRow['title'] ?? $defaultTitle);
        $description = (string)($pageRow['description'] ?? $defaultDescription);
        $imgUrl = (string)($pageRow['img_url'] ?? $defaultImg);
        $faviconUrl = (string)($pageRow['favicon_url'] ?? $defaultFavicon);
        $themeColor = (string)($pageRow['theme_color'] ?? $defaultThemeColor);
        $twitterSite = (string)($pageRow['twitter_site'] ?? $defaultTwitterSite);

        $faviconUrl = $faviconUrl !== '' ? Router::url($faviconUrl, true) : '';
        if ($imgUrl === '') {
            $imgUrl = $faviconUrl;
        } else {
            $imgUrl = Router::url($imgUrl, true);
        }

        $title = str_replace(
            ['{TITLE}', '{WEBSITE_NAME}'],
            [$titleFallback, $websiteNameFallback],
            $titleTemplate
        );

        return [
            'title' => $title,
            'description' => $description,
            'img_url' => $imgUrl,
            'favicon_url' => $faviconUrl,
            'theme_color' => $themeColor,
            'twitter_site' => $twitterSite,
        ];
    }
}
