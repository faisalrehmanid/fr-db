<?php

namespace FR\Db;

/**
 * @author Faisal Rehman <faisalrehmanid@hotmail.com>
 * 
 * This class provide abstraction layer of database 
 * and always create object of type \FR\Db\DbInterface
 * 
 * Why not using __construct() instead of init()?
 * Because constructors don't return anything
 * and init() return object based on specific database driver selection.
 * 
 * 
 * Example: How to use this class?
 * 
 * ```
 * <?php
 *      $DB = new \FR\Db\DbFactory();
 *      $DB = $DB->init($config); // Check init() docblock comments for $config
 *      $query = " select * from users where user_email = :user_email ";
 *      $values = array(':user_email' => 'email@email.com');
 *      $result = $DB->fetchRow($query, $values); // $result is single row array
 * ?>
 * ```
 */
class DbFactory
{
    /**
     * Initialize or create database object based on driver selection
     * 
     * // PDO MySQL connection configuration
     * $config =  array( 'driver' => 'pdo_mysql',
     *                   'hostname' => 'localhost',
     *                   'port' => '3306',
     *                   'username' => 'root',
     *                   'password' => '',
     *                   'database' => 'database-name',
     *                   'charset' => 'utf8mb4');
     *
     * // Oracle connection configuration
     * $config =  array( 'driver' => 'oci8',
     *                   'connection' => 'ERPDEVDB',
     *                   'username' => 'SCHEMA-NAME',
     *                   'password' => 'SCHEMA-PASSWORD',
     *                   'character_set' => 'AL32UTF8');
     *
     * @param array $config Database connection configuration
     * @see https://docs.laminas.dev/laminas-db/adapter/ for detail configuration
     * @throws \Exception When invalid driver given in connection configuration
     * @return object \FR\Db\DbInterface
     */
    public function init(array $config)
    {
        $driver = strtolower($config['driver']);

        if (in_array($driver, ['oci8'])) {
            return new Oracle\Oracle($config);
        }

        if (in_array($driver, ['pdo_mysql'])) {
            return new MySQL\MySQL($config);
        }

        $drivers = ['pdo_mysql', 'oci8'];
        throw new \Exception('Invalid driver. Driver must be: ' . implode(', ', $drivers));
    }
}
