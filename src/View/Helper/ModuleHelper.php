<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Service\ConfigurationService;
use App\View\Modules\ModuleManager;
use Cake\View\Helper;
use Cake\View\View;

final class ModuleHelper extends Helper
{
    public static mixed $vars = null;

    private ModuleManager $manager;
    private ConfigurationService $configuration;

    public function __construct(View $View, array $config = [])
    {
        parent::__construct($View, $config);

        $this->manager = new ModuleManager();
        $this->configuration = new ConfigurationService();
    }

    public function load(string $name): string|false
    {
        $name = trim($name);
        if ($name === '' || !preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
            return false;
        }

        $list = $this->manager->listModules();
        if (!isset($list[$name])) {
            return false;
        }

        $vars = $this->getView()->getVars();
        if (is_array(self::$vars)) {
            $vars = array_merge(self::$vars, $vars);
        }

        $vars['Configuration'] = $this->configuration;
        $vars['Html'] = $this->_View->Html;

        extract($vars, EXTR_SKIP);

        $theme = $this->configuration->getThemeName();

        $html = '';

        foreach ($list[$name] as $pluginSlug) {
            $file = $this->manager->resolveModuleFile($theme, (string)$pluginSlug, $name);
            if ($file === null) {
                continue;
            }

            ob_start();
            include $file;
            $html .= "\n" . ob_get_clean();
        }

        return $html;
    }
}
