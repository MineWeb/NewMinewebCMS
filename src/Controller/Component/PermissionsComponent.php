<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;

class PermissionsComponent extends Component
{
    public $permissions = [
        'COMMENT_NEWS',
        'LIKE_NEWS',
        'DELETE_HIS_COMMENT',
        'DELETE_COMMENT',
        'EDIT_HIS_EMAIL',
        'ACCESS_DASHBOARD',
        'SEND_SERVER_COMMAND_FROM_DASHBOARD',
        'MANAGE_NEWS',
        'MANAGE_SLIDER',
        'MANAGE_PAGE',
        'MANAGE_NAV',
        'MANAGE_SEO',
        'BYPASS_MAINTENANCE',
        'BYPASS_BAN',
        'MANAGE_MAINTENANCE',
        'MANAGE_CONFIGURATION',
        'USE_ADMIN_HELP',
        'MANAGE_PERMISSIONS',
        'MANAGE_PLUGINS',
        'MANAGE_API',
        'MANAGE_SERVERS',
        'MANAGE_NOTIFICATIONS',
        'VIEW_STATISTICS',
        'MANAGE_THEMES',
        'MANAGE_USERS',
        'MANAGE_BAN',
        'MANAGE_SOCIAL',
        'VIEW_WEBSITE_HISTORY',
        'VIEW_WEBSITE_LOGS'
    ];

    public $ranks = [];
    private $userModel;
    private $controller;

    function initialize(array $config): void
    {
        $this->controller = $this->_registry->getController();
        $this->userModel = TableRegistry::getTableLocator()->get("User");
        $this->permModel = TableRegistry::getTableLocator()->get("Permission");
        $this->rankModel = TableRegistry::getTableLocator()->get("Rank");

        $this->controller->set('Permissions', $this);
    }

    public function can($perm)
    {
        if (!$this->userModel->isConnected())
            return false;
        if ($this->userModel->isAdmin())
            return true;
        return $this->have($this->userModel->getKey('rank'), $perm);
    }

    public function have($rank, $perm)
    {
        if ($rank == 3 || $rank == 4)
            return true;
        return in_array($perm, $this->getRankPermissions($rank));
    }

    public function getRankPermissions($rank)
    {
        if (isset($this->ranks[$rank]))
            return $this->ranks[$rank];

        $search = $this->permModel->find('all', ['conditions' => ['rank' => $rank]])->first();
        if (!$search || !is_array(($search = unserialize($search['permissions']))))
            return $this->ranks[$rank] = [];

        return $this->ranks[$rank] = $search;
    }

    public function get_all()
    {
        $permissionsList = $this->permissions;
        $this->EyPlugin = $this->controller->EyPlugin;

        foreach ($this->EyPlugin->getPluginsActive() as $id => $plugin) {
            foreach ($plugin->permissions->available as $permission) {
                $permissionsList[] = $permission;
            }
        }

        $customRanks = $this->rankModel->find()->all();
        $permissions = [];
        foreach ($permissionsList as $permission) {
            $permissions[$permission] = [
                0 => $this->have(0, $permission),
                2 => $this->have(2, $permission),
            ];
            foreach ($customRanks as $rank) {
                $rank = $rank['rank_id'];
                $permissions[$permission][$rank] = $this->have($rank, $permission);
            }
        }
        return $permissions;
    }

}
