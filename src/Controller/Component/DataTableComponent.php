<?php
declare(strict_types=1);

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\ORM\Table;
use DateTimeInterface;
use InvalidArgumentException;
use Stringable;

final class DataTableComponent extends Component
{
    public int $emptyElements = 0;
    public array $fields = [];
    public bool $mDataProp = false;

    private ?Table $table = null;

    public function setTable(Table $table): void
    {
        $this->table = $table;
    }

    public function setTableAlias(string $alias): void
    {
        $this->table = $this->getController()->fetchTable($alias);
    }

    public function getResponse(?Table $table = null, array $options = []): array
    {
        $table = $table ?? $this->table;
        if (!$table) {
            throw new InvalidArgumentException('No table provided. Call setTable() or pass a Table to getResponse().');
        }

        $controller = $this->getController();
        $request = $controller->getRequest();
        $q = $request->getQueryParams();

        $dt = $this->parseRequest($q);

        $paginate = [];
        if (property_exists($controller, 'paginate')) {
            $paginate = $controller->paginate;
        }

        $baseFields = $options['fields'] ?? $this->fields ?? ($paginate['fields'] ?? []);
        $baseConditions = $options['conditions'] ?? ($paginate['conditions'] ?? []);
        $baseContain = $options['contain'] ?? ($paginate['contain'] ?? []);
        $baseOrder = $options['order'] ?? ($paginate['order'] ?? []);
        $findOptions = $options['find'] ?? [];

        $baseQuery = $table->find('all', ...$findOptions);

        if (!empty($baseContain)) {
            $baseQuery = $baseQuery->contain($baseContain);
        }

        if (!empty($baseConditions)) {
            $baseQuery = $baseQuery->where($baseConditions);
        }

        $total = (clone $baseQuery)->count();

        $filteredQuery = clone $baseQuery;

        if ($dt['hasSearch']) {
            $searchConditions = $this->buildSearchConditions($dt, $baseFields);
            if (!empty($searchConditions)) {
                $filteredQuery = $filteredQuery->where(['OR' => $searchConditions]);
            }
        }

        $filteredTotal = (clone $filteredQuery)->count();

        if (!empty($baseOrder)) {
            $filteredQuery = $filteredQuery->orderBy($baseOrder);
        }

        $order = $this->buildOrder($dt, $baseFields);
        if (!empty($order)) {
            $filteredQuery = $filteredQuery->orderBy($order);
        }

        if ($dt['length'] !== -1) {
            $filteredQuery = $filteredQuery
                ->limit($dt['length'])
                ->offset($dt['start']);
        }

        $rows = $filteredQuery->all();

        $aaData = [];
        foreach ($rows as $row) {
            if ($this->mDataProp || ($options['asAssoc'] ?? false)) {
                $aaData[] = $this->normalizeRow($row);
                continue;
            }

            $flat = $this->flattenRow($row, $baseFields);

            if ($this->emptyElements > 0) {
                $flat = array_pad($flat, count($flat) + $this->emptyElements, '');
            }

            $aaData[] = array_values($flat);
        }

        return [
            'recordsTotal' => $total,
            'recordsFiltered' => $filteredTotal,
            'sEcho' => $dt['sEcho'],
            'aaData' => $aaData,
        ];
    }

    private function parseRequest(array $q): array
    {
        $isModern = array_key_exists('draw', $q) || array_key_exists('columns', $q) || array_key_exists('start', $q);

        if ($isModern) {
            $draw = isset($q['draw']) ? (int)$q['draw'] : 1;
            $start = isset($q['start']) ? (int)$q['start'] : 0;
            $length = isset($q['length']) ? (int)$q['length'] : 10;

            $search = '';
            if (isset($q['search']['value']) && is_array($q['search'])) {
                $search = (string)$q['search']['value'];
            }

            $columns = [];
            if (isset($q['columns']) && is_array($q['columns'])) {
                foreach ($q['columns'] as $idx => $col) {
                    if (!is_array($col)) {
                        continue;
                    }
                    $columns[(int)$idx] = [
                        'data' => isset($col['data']) ? (string)$col['data'] : '',
                        'searchable' => !isset($col['searchable']) || (string)$col['searchable'] === 'true' || $col['searchable'] === true,
                    ];
                }
            }

            $order = [];
            if (isset($q['order']) && is_array($q['order'])) {
                foreach ($q['order'] as $o) {
                    if (!is_array($o)) {
                        continue;
                    }
                    $order[] = [
                        'column' => isset($o['column']) ? (int)$o['column'] : 0,
                        'dir' => isset($o['dir']) ? (string)$o['dir'] : 'asc',
                    ];
                }
            }

            return [
                'sEcho' => $draw,
                'start' => max(0, $start),
                'length' => $length,
                'search' => $search,
                'hasSearch' => trim($search) !== '',
                'columns' => $columns,
                'order' => $order,
            ];
        }

        $sEcho = isset($q['sEcho']) ? (int)$q['sEcho'] : 1;
        $start = isset($q['iDisplayStart']) ? (int)$q['iDisplayStart'] : 0;
        $length = isset($q['iDisplayLength']) ? (int)$q['iDisplayLength'] : 10;
        $search = isset($q['sSearch']) ? (string)$q['sSearch'] : '';

        $columns = [];
        $iColumns = isset($q['iColumns']) ? (int)$q['iColumns'] : 0;

        for ($i = 0; $i < $iColumns; $i++) {
            $columns[$i] = [
                'data' => isset($q['mDataProp_' . $i]) ? (string)$q['mDataProp_' . $i] : '',
                'searchable' => !isset($q['bSearchable_' . $i]) || (string)$q['bSearchable_' . $i] === 'true',
            ];
        }

        $order = [];
        if (isset($q['iSortCol_0'])) {
            $order[] = [
                'column' => (int)$q['iSortCol_0'],
                'dir' => isset($q['sSortDir_0']) ? (string)$q['sSortDir_0'] : 'asc',
            ];
        }

        return [
            'sEcho' => $sEcho,
            'start' => max(0, $start),
            'length' => $length === -1 ? -1 : max(-1, $length),
            'search' => $search,
            'hasSearch' => trim($search) !== '',
            'columns' => $columns,
            'order' => $order,
        ];
    }

    private function buildOrder(array $dt, array $baseFields): array
    {
        if (empty($dt['order'])) {
            return [];
        }

        $idx = (int)$dt['order'][0]['column'];
        $dir = strtolower((string)$dt['order'][0]['dir']) === 'desc' ? 'DESC' : 'ASC';

        if ($this->mDataProp && isset($dt['columns'][$idx]['data']) && $dt['columns'][$idx]['data'] !== '') {
            return [$dt['columns'][$idx]['data'] => $dir];
        }

        if (!empty($baseFields) && isset($baseFields[$idx])) {
            return [$baseFields[$idx] => $dir];
        }

        return [];
    }

    private function buildSearchConditions(array $dt, array $baseFields): array
    {
        $term = trim((string)$dt['search']);
        if ($term === '') {
            return [];
        }

        $columns = $dt['columns'] ?? [];
        $conditions = [];

        if ($this->mDataProp && !empty($columns)) {
            foreach ($columns as $col) {
                $searchable = $col['searchable'] ?? true;
                $field = $col['data'] ?? '';
                if ($searchable && is_string($field) && $field !== '') {
                    $conditions[] = [$field . ' LIKE' => '%' . $term . '%'];
                }
            }

            return $conditions;
        }

        $fields = [];
        if (!empty($this->fields)) {
            $fields = $this->fields;
        } elseif (!empty($baseFields)) {
            $fields = $baseFields;
        }

        foreach ($fields as $field) {
            if (!is_string($field) || $field === '') {
                continue;
            }
            $conditions[] = [$field . ' LIKE' => '%' . $term . '%'];
        }

        return $conditions;
    }

    private function normalizeRow(mixed $row): mixed
    {
        if (is_object($row)) {
            if (method_exists($row, 'toArray')) {
                return $this->normalizeRow($row->toArray());
            }
            if ($row instanceof DateTimeInterface) {
                return $row->format('Y-m-d H:i:s');
            }

            return (string)$row;
        }

        if (is_array($row)) {
            return array_map(function ($v) {
                return $this->normalizeRow($v);
            }, $row);
        }

        return $row;
    }

    private function flattenRow(object $row, array $fieldOrder): array
    {
        $data = $this->normalizeRow($row);

        if (!is_array($data)) {
            return [];
        }

        $flat = $this->flattenArray($data);

        if (empty($fieldOrder)) {
            ksort($flat);

            return $flat;
        }

        $ordered = [];
        foreach ($fieldOrder as $idx => $field) {
            if (!is_string($field) || $field === '') {
                continue;
            }
            $ordered[chr((int)$idx)] = array_key_exists($field, $flat) ? (string)$flat[$field] : '';
        }

        ksort($ordered);

        return $ordered;
    }

    private function flattenArray(array $data, ?string $prefix = null): array
    {
        $out = [];

        foreach ($data as $k => $v) {
            if (!is_string($k) && !is_int($k)) {
                continue;
            }

            $key = $prefix !== null ? $prefix . '.' . $k : (string)$k;

            if (is_array($v)) {
                $out += $this->flattenArray($v, $key);
                continue;
            }

            if ($v instanceof DateTimeInterface) {
                $out[$key] = $v->format('Y-m-d H:i:s');
                continue;
            }

            if (is_object($v)) {
                if (method_exists($v, 'toArray')) {
                    $out += $this->flattenArray($this->normalizeRow($v), $key);
                } elseif ($v instanceof Stringable) {
                    $out[$key] = (string)$v;
                } else {
                    $out[$key] = '';
                }
                continue;
            }

            $out[$key] = $v;
        }

        return $out;
    }
}
