<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Service\SeoService;
use Cake\Core\Configure;
use Cake\I18n\I18n;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\View\Helper;

final class SeoHelper extends Helper
{
    use LocatorAwareTrait;

    private ?array $seoConfigCache = null;

    public function htmlLang(bool $short = true): string
    {
        $locale = str_replace('_', '-', I18n::getLocale());

        return $short
            ? strtolower(substr($locale, 0, 2))
            : $locale;
    }

    public function getTitle(?string $fallback = null): string
    {
        $cfg = $this->getSeoConfig();

        if (!empty($cfg['title'])) {
            return (string)$cfg['title'];
        }

        return $fallback ?: 'MineWeb';
    }

    public function favicon(): string
    {
        $cfg = $this->getSeoConfig();

        if (empty($cfg['favicon_url'])) {
            return '';
        }

        return '<link rel="icon" type="image/png" href="' . h($cfg['favicon_url']) . '"/>';
    }

    public function metaTags(): string
    {
        $cfg = $this->getSeoConfig();

        $out = [];

        if (!empty($cfg['title'])) {
            $out[] = '<meta name="title" content="' . h($cfg['title']) . '">';
            $out[] = '<meta property="og:title" content="' . h($cfg['title']) . '">';
        }

        if (!empty($cfg['description'])) {
            $out[] = '<meta name="description" content="' . h($cfg['description']) . '">';
            $out[] = '<meta property="og:description" content="' . h($cfg['description']) . '">';
        }

        if (!empty($cfg['img_url'])) {
            $out[] = '<meta property="og:image" content="' . h($cfg['img_url']) . '">';
        }

        if (!empty($cfg['theme_color'])) {
            $out[] = '<meta name="theme-color" content="' . h($cfg['theme_color']) . '">';
        }

        $out[] = '<meta name="twitter:card" content="summary">';

        if (!empty($cfg['twitter_site'])) {
            $out[] = '<meta name="twitter:site" content="' . h($cfg['twitter_site']) . '">';
        }

        return implode("\n", $out);
    }

    private function getSeoConfig(): array
    {
        if (is_array($this->seoConfigCache)) {
            return $this->seoConfigCache;
        }

        if (!Configure::read('Install.dbConfigured')) {
            return $this->seoConfigCache = [];
        }

        $view = $this->getView();
        $request = $view->getRequest();

        $titleFallback = (string)($view->get('title') ?? $view->get('title_for_layout') ?? 'MineWeb');
        $websiteNameFallback = (string)($view->get('website_name') ?? 'MineWeb');

        $seoTable = $this->fetchTable('Seo');

        $service = new SeoService();
        $this->seoConfigCache = $service->build(
            $seoTable,
            $request->getRequestTarget(),
            $titleFallback,
            $websiteNameFallback,
            $service->isSqliteDefaultConnection()
        );

        return $this->seoConfigCache;
    }
}
