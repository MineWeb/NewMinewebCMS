<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use App\Model\Table\RolesTable;
use App\Service\PermissionService;
use App\Service\RoleService;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;

final class RolesController extends AppController
{
    private RolesTable $Roles;
    private PermissionService $permissionService;
    private RoleService $roleService;

    public function initialize(): void
    {
        parent::initialize();

        $this->Roles = $this->fetchTable('Roles');
        $this->permissionService = new PermissionService();
        $this->roleService = new RoleService();
    }

    public function index(): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_ROLES')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('ROLES__LABEL'));

        $roles = $this->Roles
            ->find()
            ->orderBy(['sort' => 'ASC', 'id' => 'ASC'])
            ->all()
            ->toArray();

        $permissionsList = $this->permissionService->list();

        $rolePerms = [];
        foreach ($roles as $r) {
            $decoded = json_decode((string)($r->permissions ?? '[]'), true);
            $list = is_array($decoded) ? $decoded : [];

            $clean = [];
            foreach ($list as $v) {
                $s = trim((string)$v);
                if ($s !== '') {
                    $clean[] = $s;
                }
            }

            $rolePerms[(int)$r->id] = array_values(array_unique($clean));
        }

        if ($this->getRequest()->is('post')) {
            $this->savePermissionsFromPost($roles, $permissionsList);

            $roles = $this->Roles
                ->find()
                ->orderBy(['sort' => 'ASC', 'id' => 'ASC'])
                ->all()
                ->toArray();

            $rolePerms = [];
            foreach ($roles as $r) {
                $decoded = json_decode((string)($r->permissions ?? '[]'), true);
                $list = is_array($decoded) ? $decoded : [];

                $clean = [];
                foreach ($list as $v) {
                    $s = trim((string)$v);
                    if ($s !== '') {
                        $clean[] = $s;
                    }
                }

                $rolePerms[(int)$r->id] = array_values(array_unique($clean));
            }

            $this->set('messages', __('PERMISSIONS__SUCCESS_SAVE'));
        }

        $matrix = [];
        foreach ($permissionsList as $perm) {
            $matrix[$perm] = [];
            foreach ($roles as $r) {
                $rid = (int)$r->id;
                $matrix[$perm][$rid] = in_array($perm, $rolePerms[$rid] ?? [], true);
            }
        }

        $this->set(compact('roles', 'permissionsList', 'matrix'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Roles')
            ->setTemplate('index');

        return null;
    }

    public function add(): Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_ROLES')) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $request = $this->getRequest();
        if (!$request->is('ajax') || !$request->is('post')) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        $name = trim((string)$request->getData('name', ''));
        if ($name === '') {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $slug = $this->slugify($name);
        if ($slug === '') {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $exists = $this->Roles->find()->select(['id'])->where(['slug' => $slug])->first();
        if ($exists) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__ALREADY_EXISTS'),
            ]));
        }

        $lastSort = $this->Roles->find()->select(['sort'])->orderBy(['sort' => 'DESC'])->first();
        $sort = $lastSort ? (int)$lastSort->sort + 10 : 100;

        $entity = $this->Roles->newEmptyEntity();
        $entity->set('name', $name);
        $entity->set('slug', $slug);
        $entity->set('sort', $sort);
        $entity->set('is_system', 0);
        $entity->set('is_default', 0);
        $entity->set('permissions', '[]');

        if ($this->Roles->save($entity)) {
            return $this->response->withStringBody(json_encode([
                'status' => true,
                'messages' => __('ROLES__ADD_SUCCESS'),
                'data' => ['id' => (int)$entity->id],
            ]));
        }

        return $this->response->withStringBody(json_encode([
            'status' => false,
            'messages' => __('ERROR__INTERNAL_ERROR'),
        ]));
    }

    public function delete(int|string $id): Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_ROLES')) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $request = $this->getRequest();
        if (!$request->is('ajax') || !$request->is('post')) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        $roleId = (int)$id;
        $role = $this->Roles->get($roleId);

        if ((int)$role->is_system === 1 || (int)$role->is_default === 1 || (string)$role->slug === RoleService::ADMIN_SLUG) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FORBIDDEN'),
            ]));
        }

        if ($this->Roles->delete($role)) {
            $this->permissionService->clearCache($roleId);

            return $this->response->withStringBody(json_encode([
                'status' => true,
                'messages' => __('ROLES__DELETE_SUCCESS'),
            ]));
        }

        return $this->response->withStringBody(json_encode([
            'status' => false,
            'messages' => __('ERROR__INTERNAL_ERROR'),
        ]));
    }

    private function savePermissionsFromPost(array $roles, array $permissionsList): void
    {
        $request = $this->getRequest();
        $data = (array)$request->getData();

        $rolesById = [];
        foreach ($roles as $r) {
            $rolesById[(int)$r->id] = $r;
        }

        $byRole = array_map(function () {
            return [];
        }, $rolesById);

        foreach ($data as $key => $checked) {
            if (is_array($checked)) {
                continue;
            }

            $key = (string)$key;
            if (!str_contains($key, '-')) {
                continue;
            }

            [$perm, $roleIdRaw] = explode('-', $key, 2);
            $perm = trim($perm);
            $rid = (int)$roleIdRaw;

            if ($perm === '' || !isset($byRole[$rid])) {
                continue;
            }

            if (!in_array($perm, $permissionsList, true)) {
                continue;
            }

            $byRole[$rid][] = $perm;
        }

        foreach ($byRole as $roleId => $perms) {
            $role = $rolesById[$roleId] ?? null;
            if ($role === null) {
                continue;
            }

            if ((string)$role->slug === RoleService::ADMIN_SLUG) {
                continue;
            }

            $clean = [];
            foreach ($perms as $p) {
                $s = trim((string)$p);
                if ($s !== '') {
                    $clean[] = $s;
                }
            }
            $clean = array_values(array_unique($clean));

            $entity = $this->Roles->get((int)$roleId);
            $entity->set('permissions', json_encode($clean, JSON_UNESCAPED_UNICODE));
            $this->Roles->save($entity);

            $this->permissionService->clearCache((int)$roleId);
        }
    }

    private function slugify(string $name): string
    {
        $s = trim($name);

        $map = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a', 'å' => 'a',
            'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ñ' => 'n',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
            'À' => 'a', 'Á' => 'a', 'Â' => 'a', 'Ä' => 'a', 'Ã' => 'a', 'Å' => 'a',
            'Ç' => 'c',
            'È' => 'e', 'É' => 'e', 'Ê' => 'e', 'Ë' => 'e',
            'Ì' => 'i', 'Í' => 'i', 'Î' => 'i', 'Ï' => 'i',
            'Ñ' => 'n',
            'Ò' => 'o', 'Ó' => 'o', 'Ô' => 'o', 'Ö' => 'o', 'Õ' => 'o',
            'Ù' => 'u', 'Ú' => 'u', 'Û' => 'u', 'Ü' => 'u',
            'Ý' => 'y',
        ];

        $s = strtr($s, $map);
        $s = mb_strtolower($s, 'UTF-8');
        $s = preg_replace('/[^a-z0-9]+/', '_', $s) ?? '';

        return trim($s, '_');
    }
}
