<?php

namespace ProductImporter;

class Csv 
{
    /**
     * Output Sorted Data
     *
     * @var array
     */
    protected $data = [];

    /**
     * Raw Input Data
     *
     * @var array
     */
    protected $raw = [];

    /**
     * Column Keys
     *
     * @var array
     */
    protected $keys = [];

    /**
     * Sorting Rules
     *
     * @var array
     */
    protected $rules = [];

    protected $allowedTypes = ['json', 'plain'];

    /**
     * Import CSV from a local file
     *
     * @param string $file
     * @return \ProductImporter\Csv
     */
    public function __construct(string $file)
    {
        if(!$file = file($file, FILE_SKIP_EMPTY_LINES)) {
            throw new \Exception('Could not find this file.');
        }
        $this->raw = array_map('str_getcsv', $file);
        $this->keys = array_shift($this->raw);
        foreach ($this->raw as $key => $value) $this->raw[$key] = array_combine($this->keys, $value);
        return $this;
    }

    /**
     * Group By Specifiec Field
     *
     * @param string $field
     * @return \ProductImporter\Csv
     */
    public function groupBy(string $field) : \ProductImporter\Csv
    {
        $this->data = $this->arrayGroupBy($this->raw, function($value) use ($field) {
            return $value[$field];
        });
        $this->data = array_values($this->data);
        return $this;
    }

    /**
     * Add Rules for querying
     *
     * @param string $name
     * @param string $field
     * @param array $order
     * @return \ProductImporter\Csv
     */
    public function addRule(string $name, string $field, array $order) : \ProductImporter\Csv
    {
        $this->rules[$name] = [];
        $this->rules[$name]['field'] = $field;
        $this->rules[$name]['order'] = $order;
        return $this;
    }

    /**
     * Sort By Rule
     *
     * @param string $field
     * @return \ProductImporter\Csv
     */
    public function sortByRule(string $field) : \ProductImporter\Csv
    {
        foreach($this->data as $key => $value) {
            usort($this->data[$key], function($a, $b) use ($field) {
                if(!isset($this->rules[$a[$field]])) {
                    throw new \Exception('This Rule does not exist!');
                }

                $ruleA = $this->rules[$a[$field]];
                $ruleB = $this->rules[$b[$field]];

                $position_a = array_search($a[$ruleA['field']], $ruleA['order']);
                $position_b = array_search($b[$ruleB['field']], $ruleB['order']);
                return $position_a - $position_b;
            });
        }
        return $this;
    }

    /**
     * Map Array To a new Structure
     *
     * @param array $structure
     * @return \ProductImporter\Csv
     */
    public function mapToStructure(array $structure) : \ProductImporter\Csv
    {
        $array = [];
        foreach($this->data as $dataKey => $dataVal) {
            foreach($structure as $strKey => $strVal) {
                $i = 0;
                foreach($dataVal as $dKey => $dVal) {
                    if(is_array($strVal)) {
                        $array[$dataKey][$strKey][$i] = [];
                        foreach($strVal as $sKey => $sVal) {
                            $array[$dataKey][$strKey][$i][$sVal] = $dVal[$sVal];
                        }
                        $i++;

                    } else {
                        $array[$dataKey][$strVal] = $dVal[$strVal];
                    }
                }
            }
        }
        $this->data = $array;
        return $this;
    }

    /**
     * Fetch All Products
     *
     * @param string $type
     * @return mixed
     */
    public function get(string $type = 'json')
    {
        return $this->toType($type, $this->data);
    }

    /**
     * Fetch First Product
     *
     * @param string $type
     * @return mixed
     */
    public function first(string $type = 'json')
    {
        return $this->toType($type, array_shift($this->data));
    }

    /**
     * Fetch Last Product
     *
     * @param string $type
     * @return mixed
     */
    public function last(string $type = 'json')
    {
        return $this->toType($type, end($this->data));
    }

    /**
     * Convert Data to Specific Type.
     *
     * @param string $type
     * @param array $data
     * @return mixed
     */
    protected function toType(string $type, array $data)
    {
        if(!in_array($type, $this->allowedTypes)) {
            throw new \Exception('The type `' . $type . '` does not exist');
        }
        return $this->{$type}($data);
    }

    /**
     * Convert Data to JSON
     *
     * @param array $data
     * @return void
     */
    protected function json(array $data)
    {
        return json_encode($data);
    }

    /**
     * Convert Data to a plain Array
     *
     * @param array $data
     * @return void
     */
    protected function plain(array $data)
    {
        return $data;
    }

    /**
     * Cycle through array and group by selector
     *
     * @param array $array
     * @param callable $selector
     * @return array
     */
    protected function arrayGroupBy(array $array, callable $selector) : array
    {
        $results = [];
        foreach ($array as $value) $results[call_user_func($selector, $value)][] = $value;
        return $results;
    }
}