<?php

namespace FR\Db;

use Laminas\Db\Adapter\Adapter;
use FR\Db\ExpressionInterface;
use FR\Db\SQLFragment;

/**
 * This class implements common functions from \FR\Db\ExpressionInterface
 * These functions are common in all type of databases. Each specific 
 * database expression class must extend this class.
 * 
 * This class dependent on Laminas/Db module
 * 
 * Why abstract class?
 * Don't allow to create an object of this class instead it can be 
 * only inherit from base class.
 */
abstract class ExpressionAbstract implements ExpressionInterface
{
    /**
     * @var object \Laminas\Db\Adapter\Adapter
     */
    protected $adapter;

    /**
     * @param object \Laminas\Db\Adapter\Adapter $adapter
     */
    public function __construct(Adapter $adapter)
    {
        // Set Laminas\Db\Adapter\Adapter
        $this->adapter = $adapter;
    }

    /**
     * Get \Laminas\Db\Adapter\Adapter object
     *
     * @return object \Laminas\Db\Adapter\Adapter
     */
    protected function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Validate and format database column name
     *
     * @param string $column name like: table.column, column
     * @throws \Exception Symbol quote identifier not allowed in column name
     * @throws \Exception Space not allowed in column name
     * @throws \Exception column name could not be empty
     * @return string Formated column name
     */
    protected function validateAndFormatColumnName($column)
    {
        if (empty($column))
            throw new \Exception('Column name could not be empty');

        // Quote not allowed
        $symbol = $this->getAdapter()->getPlatform()->getQuoteIdentifierSymbol();
        if (strpos($column, $symbol) !== false)
            throw new \Exception('Symbol ' . $symbol . ' not allowed in column name');

        // Space not allowed            
        if (strpos($column, ' ') !== false)
            throw new \Exception('Space not allowed in column name');

        return $this->getAdapter()->getPlatform()->quoteIdentifierInFragment($column);
    }

    /**
     * Generate unique key
     *
     * @param string $value 
     * @return string Unique key with :colon
     */
    protected function generateUniqueKey($value = '')
    {
        // Prefix with :key_ because md5() return Hex value which create problems
        $key = ':key_' . md5(uniqid() . $value . microtime());

        // Should not more than 16 char otherwise create Exception: Identifier is too long
        return substr($key, 0, 16);
    }

    /**
     * Use this function for `SELECT`, `UPDATE`, `DELETE` query to 
     * prevent SQL Injection for IN Clause
     *
     * @param array $array Single dimension array like ['value-1', 'value-2', ...]
     * @return object \FR\Db\SQLFragmentInterface
     */
    public function in(array $array = [])
    {
        if (empty($array)) {
            return new SQLFragment('', []);
        }

        $keys = [];
        $values = [];
        foreach ($array as $k => $value) {
            $key = $this->generateUniqueKey($k);
            $keys[] =  $key;
            $values[$key] = $value;
        }
        $keys = implode(', ', $keys);

        $fragment = ' IN (' . $keys . ') ';
        return new SQLFragment($fragment, $values);
    }
}
