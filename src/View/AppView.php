<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.0.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\View;

use Cake\View\View;

/**
 * Application View
 *
 * Your application's default view class
 *
 * @link https://book.cakephp.org/4/en/views.html#the-app-view
 */

/**
 * @property \App\View\Helper\LangHelper $Lang
 * @property \App\View\Helper\AuthHelper $Auth
 * @property \App\View\Helper\SeoHelper $Seo
 * @property \App\View\Helper\NavbarHelper $Navbar
 * @property \App\View\Helper\WebsiteInfoHelper $WebsiteInfo
 * @property \App\View\Helper\AdminUiHelper $AdminUi
 * @property \App\View\Helper\ConfigHelper $Config
 * @property \App\View\Helper\SocialButtonHelper $SocialButtons
 * @property \App\View\Helper\ModuleHelper $Module
 * @property \App\View\Helper\UpdateHelper $Update
 * @property \App\View\Helper\PluginHelper $Plugin
 * @property \App\View\Helper\ServerBridgeHelper $ServerBridge
 */
class AppView extends View
{
    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading helpers.
     *
     * e.g. `$this->loadHelper('Html');`
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadHelper('Lang');
        $this->loadHelper('Auth');
        $this->loadHelper('Seo');
        $this->loadHelper('Navbar');
        $this->loadHelper('WebsiteInfo');
        $this->loadHelper('AdminUi');
        $this->loadHelper('Config');
        $this->loadHelper('SocialButton');
        $this->loadHelper('Module');
        $this->loadHelper('Update');
        $this->loadHelper('Plugin');
        $this->loadHelper('ServerBridge');
    }
}
