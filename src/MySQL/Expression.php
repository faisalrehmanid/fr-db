<?php

namespace FR\Db\MySQL;

use Laminas\Db\Adapter\Adapter;
use FR\Db\ExpressionAbstract;
use FR\Db\SQLFragment;

/**
 * This class implements \FR\Db\ExpressionInterface specifically for MySQL
 */
class Expression extends ExpressionAbstract
{
    /**
     * @param object \Laminas\Db\Adapter\Adapter $adapter
     */
    public function __construct(Adapter $adapter)
    {
        parent::__construct($adapter);
    }

    /**
     * Use this function for `SELECT` query to get datetime from database in `Y-m-d H:i:s` format
     *
     * @param string $column name like: table.column, column
     * @param bool $time if true then it will return date with time. Default is true
     * @throws \Exception Symbol quote identifier not allowed in column name
     * @throws \Exception Space not allowed in column name
     * @throws \Exception column name could not be empty
     * @return string Formated SQL query fragment
     */
    public function getDate($column, $time = true)
    {
        $column = $this->validateAndFormatColumnName($column);

        if ($time == true) {
            return " DATE_FORMAT($column, '%Y-%m-%d %H:%i:%s') ";
        } else {
            return " DATE_FORMAT($column, '%Y-%m-%d') ";
        }
    }

    /**
     * Use this function for `INSERT`, `UPDATE` query to set datetime in database
     *
     * @param string $value Format must be: `Y-m-d H:i:s` or `Y-m-d`
     * @throws \Exception Invalid datetime format it must be: `Y-m-d H:i:s` or `Y-m-d`
     * @return object \FR\Db\SQLFragmentInterface
     */
    public function setDate($value = '')
    {
        if (empty($value)) {
            return new SQLFragment("''", []);
        }

        // Validate $value datetime format if space found then format must be: `Y-m-d H:i:s`
        if (strpos($value, ' ') !== false) {
            $php_format = 'Y-m-d H:i:s';
            $sql_format = '%Y-%m-%d %H:%i:%s';
        } else {
            $php_format = 'Y-m-d';
            $sql_format = '%Y-%m-%d';
        }

        // $value must be valid datetime format
        $date = \DateTime::createFromFormat($php_format, $value);
        if (($date && $date->format($php_format) == $value) == false)
            throw new \Exception('Invalid datetime format it must be: `Y-m-d H:i:s` or `Y-m-d`');

        $key = $this->generateUniqueKey($value);
        return new SQLFragment(" STR_TO_DATE($key, '" . $sql_format . "') ", array($key => $value));
    }

    /**
     * Use this function for `SELECT` query to get UUID from database
     *
     * @param string $column name like: table.column, column
     * @throws \Exception Symbol quote identifier not allowed in column name
     * @throws \Exception Space not allowed in column name
     * @throws \Exception column name could not be empty
     * @return string Formated SQL query fragment
     */
    public function getUuid($column)
    {
        $column = $this->validateAndFormatColumnName($column);
        return " LOWER(HEX(" . $column . ")) ";
    }

    /**
     * Use this function for `INSERT`, `UPDATE` query to set UUID in database
     *
     * @param string $value
     * @return object \FR\Db\SQLFragmentInterface
     */
    public function setUuid($value)
    {
        if (empty($value)) {
            return new SQLFragment("''", []);
        }

        $value = str_replace("-", "", strtolower($value));
        $key = $this->generateUniqueKey($value);
        return new SQLFragment(" UNHEX($key) ", array($key => $value));
    }
}
