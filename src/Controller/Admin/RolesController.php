<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use App\Model\Table\RolesTable;
use App\Service\PermissionService;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;

final class RolesController extends AppController
{
    private RolesTable $Roles;
    private PermissionService $permissionService;

    public function initialize(): void
    {
        parent::initialize();

        $this->Roles = $this->fetchTable('Roles');
        $this->permissionService = new PermissionService();
    }

    public function index(): ?Response
    {
        $this->assertCanManageRoles();

        $this->set('title_for_layout', __('ROLES__LABEL'));

        $permissionsList = $this->permissionService->list();

        if ($this->getRequest()->is('post')) {
            $this->savePermissionsFromPost($permissionsList);
            $this->set('messages', __('PERMISSIONS__SUCCESS_SAVE'));
        }

        $roles = $this->fetchRolesOrdered();
        $rolePerms = $this->buildRolePermissionsMap($roles);

        $matrix = $this->buildMatrix($permissionsList, $roles, $rolePerms);

        $this->set(compact('roles', 'permissionsList', 'matrix'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Roles')
            ->setTemplate('index');

        return null;
    }

    public function add(): Response
    {
        $this->assertCanManageRoles();

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $request = $this->getRequest();
        if (!$request->is('ajax') || !$request->is('post')) {
            return $this->json(['status' => false, 'messages' => __('ERROR__BAD_REQUEST')]);
        }

        $name = trim((string)$request->getData('name', ''));
        if ($name === '') {
            return $this->json(['status' => false, 'messages' => __('ERROR__FILL_ALL_FIELDS')]);
        }

        $slug = $this->slugify($name);
        if ($slug === '') {
            return $this->json(['status' => false, 'messages' => __('ERROR__FILL_ALL_FIELDS')]);
        }

        $exists = $this->Roles->find()->select(['id'])->where(['slug' => $slug])->first();
        if ($exists) {
            return $this->json(['status' => false, 'messages' => __('ERROR__ALREADY_EXISTS')]);
        }

        $sort = $this->nextSort();

        $entity = $this->Roles->newEmptyEntity();
        $entity->set('name', $name);
        $entity->set('slug', $slug);
        $entity->set('sort', $sort);
        $entity->set('is_system', 0);
        $entity->set('is_default', 0);
        $entity->set('permissions', '[]');

        if ($this->Roles->save($entity)) {
            return $this->json([
                'status' => true,
                'messages' => __('ROLES__ADD_SUCCESS'),
                'data' => ['id' => (int)$entity->id],
            ]);
        }

        return $this->json(['status' => false, 'messages' => __('ERROR__INTERNAL_ERROR')]);
    }

    public function delete(int|string $id): Response
    {
        $this->assertCanManageRoles();

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $request = $this->getRequest();
        if (!$request->is('ajax') || !$request->is('post')) {
            return $this->json(['status' => false, 'messages' => __('ERROR__BAD_REQUEST')]);
        }

        $roleId = (int)$id;
        $role = $this->Roles->get($roleId);

        if (
            (int)$role->is_system === 1
            || (int)$role->is_default === 1
            || (string)$role->slug === PermissionService::ADMIN_SLUG
        ) {
            return $this->json(['status' => false, 'messages' => __('ERROR__FORBIDDEN')]);
        }

        if ($this->Roles->delete($role)) {
            $this->permissionService->clearCache($roleId);

            return $this->json(['status' => true, 'messages' => __('ROLES__DELETE_SUCCESS')]);
        }

        return $this->json(['status' => false, 'messages' => __('ERROR__INTERNAL_ERROR')]);
    }

    private function assertCanManageRoles(): void
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_ROLES')) {
            throw new ForbiddenException();
        }
    }

    private function json(array $payload): Response
    {
        return $this->response->withStringBody(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    private function fetchRolesOrdered(): array
    {
        return $this->Roles
            ->find()
            ->orderBy(['sort' => 'ASC', 'id' => 'ASC'])
            ->all()
            ->toArray();
    }

    private function buildRolePermissionsMap(array $roles): array
    {
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

        return $rolePerms;
    }

    private function buildMatrix(array $permissionsList, array $roles, array $rolePerms): array
    {
        $matrix = [];
        foreach ($permissionsList as $perm) {
            $row = [];
            foreach ($roles as $r) {
                $rid = (int)$r->id;
                $row[$rid] = in_array($perm, $rolePerms[$rid] ?? [], true);
            }
            $matrix[$perm] = $row;
        }

        return $matrix;
    }

    private function savePermissionsFromPost(array $permissionsList): void
    {
        $request = $this->getRequest();
        $data = (array)$request->getData();

        $roles = $this->fetchRolesOrdered();

        $rolesById = [];
        foreach ($roles as $r) {
            $rolesById[(int)$r->id] = $r;
        }

        $byRole = [];
        foreach ($rolesById as $rid => $_) {
            $byRole[$rid] = [];
        }

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

            if ((string)$role->slug === PermissionService::ADMIN_SLUG) {
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

            if ($this->Roles->save($entity)) {
                $this->permissionService->clearCache((int)$roleId);
            }
        }
    }

    private function nextSort(): int
    {
        $lastSort = $this->Roles->find()->select(['sort'])->orderBy(['sort' => 'DESC'])->first();
        return $lastSort ? (int)$lastSort->sort + 10 : 100;
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
