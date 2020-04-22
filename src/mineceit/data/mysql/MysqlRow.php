<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-11-22
 * Time: 00:38
 */

declare(strict_types=1);

namespace mineceit\data\mysql;


class MysqlRow
{

    /** @var string */
    private $tableName;

    /** @var array */
    private $columns;

    public function __construct(string $table)
    {
        $this->tableName = $table;
        $this->columns = [];
    }

    /**
     * @return string
     */
    public function getTable() : string {
        return $this->tableName;
    }


    /**
     * Adds a value to the columns.
     *
     * @param string $column
     * @param $value
     */
    public function put(string $column, $value) : void {

        if(is_string($value))
            $value = "'{$value}'";
        elseif (is_bool($value))
            $value = intval($value);

        $this->columns[$column] = $value;
    }

    /**
     * @param array $statements
     * @param string $tableName
     * @return string
     *
     * Returns the query string for inserting a row.
     */
    public function queryInsert($statements = [], $tableName = '') : string {

        $tableName = strlen($tableName) <= 0 ? $this->tableName : $tableName;

        $keys = array_keys($this->columns);

        $keys = implode(", ", $keys);

        $values = implode(", ", array_values($this->columns));

        $result = "INSERT INTO {$this->tableName} ({$keys}) SELECT {$values}";

        $size = count($statements);

        if($size > 0) {

            $statement = implode(" AND ", $statements);

            $result .= " WHERE NOT EXISTS (SELECT * FROM {$tableName} WHERE {$statement})";
        }

        return $result;
    }


    /**
     * @return string
     *
     * Returns the query string for inserting and updating a duplicate.
     */
    public function queryInsertNUpdate() : string {

        $keys = array_keys($this->columns);

        $implodedKeys = implode(", ", $keys);

        $implodedValues = implode(", ", array_values($this->columns));

        $array = [];

        foreach($keys as $key) {

            $value = $this->columns[$key];

            $string = "{$key} = {$value}";

            $array[] = $string;
        }

        $imploded = implode(", ", $array);

        return "INSERT INTO {$this->tableName} ({$implodedKeys}) VALUES ({$implodedValues}) ON DUPLICATE KEY UPDATE {$imploded}";
    }


    /**
     * @param array $statements
     * @return string
     *
     * Returns the query string for inserting a row.
     */
    public function queryUpdate($statements = []) : string {

        $keys = array_keys($this->columns);

        $array = [];

        foreach($keys as $key) {

            $value = $this->columns[$key];

            $array[] = "$key = $value";
        }

        $string = implode(', ', $array);

        $result = "UPDATE {$this->tableName} SET {$string}";

        $size = count($statements);

        if($size > 0) {

            $statement = implode(" AND ", $statements);

            $result .= " WHERE {$statement}";
        }
        return $result;
    }
}