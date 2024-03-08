<?php
namespace App\Controller\Component;

use App\Controller\AppController;
use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\ORM\Table;
use Exception;

/**
 * This component provides compatibility between the dataTables jQuery plugin and CakePHP 2
 * @author chris
 * @package DataTableComponent
 * @link http://www.datatables.net/release-datatables/examples/server_side/server_side.html parts of code borrowed from dataTables example
 * @since version 1.1.1
 * Copyright (c) 2013 Chris Nizzardini
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
class DataTableComponent extends Component
{

    public int $conditionsByValidate = 0;
    public int $emptyElements = 0;
    public array $fields = [];
    public bool $mDataProp = false;
    private Table $model;
    private AppController $controller;
    private array $times = [];

    public function initialize(array $config): void
    {
        $this->controller = $this->_registry->getController();
    }

    public function setTable(Table $table): void
    {
        $this->model = $table;
    }

    /**
     * returns dataTables compatible array - just json_encode the resulting aray
     * @param object|null $controller optional
     * @param object|null $model optional
     * @return array
     * @throws Exception
     */
    public function getResponse(object $controller = null, object $model = null)
    {

        /**
         * it is no longer necessary to pass in a controller or model
         * this is handled in the initialize method
         * $controller is disregarded.
         * $model is only necessary if you are using a model from a different controller such as if you are in
         * a CustomerController but your method is displaying data from an OrdersModel.
         */

        $this->setTimes('Pre', 'start', 'Preproccessing of conditions');

        if ($model != null) {
            if (is_string($model)) {
                $this->model = $this->controller->{$model};
            } else {
                $this->model = $model;
                unset($model);
            }
        }

        $conditions = $this->controller->paginate['conditions'] ?? null;

        $isFiltered = false;

        if (!empty($conditions)) {
            $isFiltered = true;
        }

        if ($this->controller->getRequest()->getQueryParams() !== null) {
            $httpGet = $this->controller->getRequest()->getQueryParams();
        }

        // check for ORDER BY in GET request
        if (isset($httpGet) && isset($httpGet['iSortCol_0']) && $httpGet['sEcho'] != 1) {
            $orderBy = $this->getOrderByStatements();
            if (!empty($orderBy)) {
                $this->controller->paginate = array_merge($this->controller->paginate, ['order' => $orderBy]);
            }
        }

        // check for WHERE statement in GET request
        if (isset($httpGet) && !empty($httpGet['sSearch'])) {
            $conditions = $this->getWhereConditions();

            $this->controller->paginate = array_merge_recursive($this->controller->paginate, ['conditions' => ['AND' => $conditions]]);
            $isFiltered = true;
        }

        $this->setTimes('Pre', 'stop');
        $this->setTimes('Count All', 'start', 'Counts all records in the table');
        // @todo avoid multiple queries for finding count, maybe look into "SQL CALC FOUND ROWS"
        // get full count
        $this->model->recursive = -1;
        $total = $this->model->find()->count();
        $this->setTimes('Count All', 'stop');
        $this->setTimes('Filtered Count', 'start', 'Counts records that match conditions');
        $parameters = $this->controller->paginate;

        if ($isFiltered) {
            $filteredTotal = $this->model->find()->applyOptions($parameters)->count();
        }
        $this->setTimes('Filtered Count', 'stop');
        $this->setTimes('Find', 'start', 'Cake Find');

        // set sql limits
        if ($this->controller->getRequest()->getQuery('iDisplayStart') !== null && $this->controller->getRequest()->getQuery('iDisplayLength') != '-1') {
            $start = $this->controller->getRequest()->getQuery('iDisplayStart');
            $length = $this->controller->getRequest()->getQuery('iDisplayLength');
            $parameters['limit'] = (int) $length;
            $parameters['offset'] = (int) $start;
        }

        // execute sql select
        $data = $this->model->find()->applyOptions($parameters)->all();

        $this->setTimes('Find', 'stop');
        $this->setTimes('Response', 'start', 'Formatting of response');
        // dataTables compatible array
        $response = [
            'sEcho' => $this->controller->getRequest()->getQuery('sEcho') !== null ? intval($this->controller->getRequest()->getQuery('sEcho')) : 1,
            'iTotalRecords' => $total,
            'iTotalDisplayRecords' => $isFiltered === true ? $filteredTotal : $total,
            'aaData' => []
        ];

        // return data
        if (!$data) {
            return $response;
        } else {
            foreach ($data as $i) {
                if ($this->mDataProp) {
                    $response['aaData'][] = $i;
                } else {
                    $tmp = $this->getDataRecursively($i);
                    if ($this->emptyElements > 0) {
                        $tmp = array_pad($tmp, count($tmp) + $this->emptyElements, '');
                    }
                    $response['aaData'][] = array_values($tmp);
                }
            }
        }
        $this->setTimes('Response', 'stop');
        return $response;
    }

    /**
     * setTimes method - adds to timer of settings[timed] = true
     * @param string $key
     * @param string $action (start or stop)
     * @param string $desc (optional)
     */
    private function setTimes(string $key, string $action, string $desc = '')
    {
        if (isset($this->settings) && isset($this->settings['timer']) && $this->settings['timer']) {
            $this->times[$key][$action] = [
                'action' => $action,
                'time' => microtime(true),
                'description' => $desc
            ];
        }
    }

    /**
     * returns sql order by string after converting dataTables GET request into Cake style order by
     * @param void
     * @return string
     * @throws Exception
     */
    private function getOrderByStatements(): string
    {

        if (!isset($this->controller->paginate['fields']) && !empty($this->controller->paginate['contain']) && empty($this->fields)) {
            throw new Exception("Missing field and/or contain option in Paginate. Please set the fields so I know what to order by.");
        }

        $orderBy = '';

        $fields = !empty($this->fields) ? $this->fields : $this->controller->paginate['fields'];

        // loop through sorting columbns in GET
        //for ( $i=0 ; $i<intval( $this->controller->request->query['iSortingCols'] ) ; $i++ ){
        // if column is found in paginate fields list then add to $orderBy
        if ($this->mDataProp) {
            $direction = $this->controller->getRequest()->getQuery('sSortDir_0') === 'asc' ? 'asc' : 'desc';
            $mDataProp = $this->controller->getRequest()->getQuery('iSortCol_0');
            $orderBy = $this->controller->getRequest()->getQuery('mDataProp_' . $mDataProp) . ' ' . $direction . ', ';
        } else if (!empty($fields) && $this->controller->getRequest()->getQuery('iSortCol_0') !== null) {
            $direction = $this->controller->getRequest()->getQuery('sSortDir_0') === 'asc' ? 'asc' : 'desc';
            $orderBy = $fields[$this->controller->getRequest()->getQuery('iSortCol_0')] . ' ' . $direction . ', ';
        }
        //}

        if (!empty($orderBy)) {
            return substr($orderBy, 0, -2);
        }

        return $orderBy;
    }

    /**
     * returns sql conditions array after converting dataTables GET request into Cake style conditions
     * will only search on fields with bSearchable set to true (which is the default value for bSearchable)
     * @param void
     * @return array
     * @throws Exception
     */
    private function getWhereConditions(): array
    {

        if (!$this->mDataProp && !isset($this->controller->paginate['fields']) && empty($this->fields)) {
            throw new Exception("Field list is not set. Please set the fields so I know how to build where statement.");
        }

        $conditions = [];

        $fields = [];
        if ($this->mDataProp) {
            for ($i = 0; $i < $this->controller->getRequest()->getQuery('iColumns'); $i++) {
                if (!$this->controller->getRequest()->getQuery('bSearchable_' . $i) !== null || $this->controller->getRequest()->getQuery('bSearchable_' . $i)) {
                    $fields[] = $this->controller->getRequest()->getQuery('mDataProp_' . $i);
                }
            }
        } else if (!empty($this->fields) || !empty($this->controller->paginate['fields'])) {
            $fields = !empty($this->fields) ? $this->fields : $this->controller->paginate['fields'];
        }

        foreach ($fields as $x => $column) {

            // only create conditions on bSearchable fields
            if ($this->controller->getRequest()->getQuery('bSearchable_' . $x) == 'true') {

                if ($this->mDataProp) {
                    $conditions['OR'][] = [
                        $this->controller->getRequest()->getQuery('mDataProp_' . $x) . ' LIKE' => '%' . $this->controller->getRequest()->getQuery('sSearch') . '%'
                    ];
                } else {

                    list($table, $field) = explode('.', $column);

                    // attempt using definitions in $model->validate to build intelligent conditions
                    if ($this->conditionsByValidate == 1 && array_key_exists($column, $this->model->validate->getConditions())) {

                        if (!empty($this->controller->paginate['contain'])) {
                            if (array_key_exists($table, $this->controller->paginate['contain']) && in_array($field, $this->controller->paginate['contain'][$table]['fields'])) {
                                $conditions[$table]['conditions'][] = $this->conditionByDataType($column);
                            }
                        } else {
                            $conditions['OR'][] = $this->conditionByDataType($column);
                        }
                    } else {

                        if (!empty($this->controller->paginate['contain'])) {
                            if (array_key_exists($table, $this->controller->paginate['contain']) && in_array($field, $this->controller->paginate['contain'][$table]['fields'])) {
                                $conditions[$table]['conditions'][] = $column . ' LIKE "%' . $this->controller->getRequest()->getQuery('sSearch') . '%"';
                            }
                        } else {
                            $conditions['OR'][] = [
                                $column . ' LIKE' => '%' . $this->controller->getRequest()->getQuery('sSearch') . '%'
                            ];
                        }
                    }
                }
            }
        }
        return $conditions;
    }

    /**
     * looks through the models validate array to determine to create conditions based on datatype, returns condition array.
     * to enable this set $this->DataTable->conditionsByValidate = 1.
     * @param string $field
     * @return array
     */
    private function conditionByDataType(string $field): array
    {
        $condition = [];
        foreach ($this->model->validate->getConditions()[$field] as $rule => $j) {
            switch ($rule) {
                case 'boolean':
                case 'numeric':
                case 'naturalNumber':
                    $condition = [$field => $this->controller->getRequest()->getQuery('sSearch')];
                    break;
            }
        }
        return $condition;
    }

    /**
     * finds data recursively and returns a flattened key => value pair array
     * second parameter is not required and only used in callbacks to self
     * @param array $data
     * @param string|null $key
     * @return array
     */
    private function getDataRecursively(array $data, string $key = null): array
    {
        $fields = [];

        // note: the chr() function is used to produce the arrays index to make sorting via ksort() easier.

        // loop through cake query result
        foreach ($data as $x => $i) {
            // go recursive
            if (is_array($i)) {
                //if(!array_key_exists($x,$this->model->hasMany)){
                $fields = array_merge($fields, $this->getDataRecursively($i, $x));
                //}
            } // check if component was given fields explicitely
            else if (!empty($this->fields)) {
                if (in_array("$key.$x", $this->fields)) {
                    $index = array_search("$key.$x", $this->fields);
                    //echo "$key.$x = $index = $i \n";
                    // index needs to be a string so array_merge handles it properly
                    $fields[chr($index)] = "$i";
                }
            } // dimension is not multi-dimensionable so add to $fields
            else if (isset($this->controller->paginate['fields'])) {
                if (in_array("$key.$x", $this->controller->paginate['fields'])) {
                    $index = array_search("$key.$x", $this->controller->paginate['fields']);
                    // index needs to be a string so array_merge handles it properly
                    $fields[chr($index)] = "$i";
                }
            } // will try to include all results but this will likely not work for you
            else {
                $fields["$key.$x "] = "$i";
            }
        }
        ksort($fields);
        //var_dump($fields);
        return $fields;
    }

    /**
     * getTimes method - returns an array of the components benchmarks
     * @return array
     */
    public function getTimes(): array
    {
        $times = [];
        $componentStart = 0;
        $end = 0;
        foreach ($this->times as $x => $i) {
            $start = $desc = 0;
            foreach ($i as $j) {
                if ($j['action'] == 'start') {
                    $start = $j['time'];
                    $desc = $j['description'];
                    if ($componentStart == 0) {
                        $componentStart = $start;
                    }
                } else if ($j['action'] == 'stop') {
                    $end = $j['time'];
                }
                if ($start > 0 && $end > 0) {
                    $times[$x] = [
                        'description' => $desc,
                        'time' => round(($end - $start), 4),
                    ];
                }
            }
        }

        if (isset($this->settings) && isset($this->settings['timer']) && $this->settings['timer']) {
            $times['TOTAL'] = [
                'time' => round(($end - $componentStart), 4)
            ];
        }

        return $times;
    }
}
