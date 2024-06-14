<?php
namespace bundle\sql;

use php\framework\Logger;
use php\sql\SqlConnection;
use php\sql\SqlDriverManager;
use php\sql\SqlException;
use php\sql\SqlStatement;

/**
 * @package bundle\sql
 */
class PgSqlClient extends SqlClient
{
    /**
     * @var string
     */
    public $host = 'localhost';

    /**
     * @var int
     */
    public $port = 5432;

    /**
     * @var string
     */
    public $database;

    /**
     * @var string
     */
    public $username = 'postgres';

    /**
     * @var string
     */
    public $password = '';

    /**
     * @var bool
     */
    public $useSSL = false;

    /**
     * @var int
     */
    public $loginTimeout = 0;

    /**
     * @var int
     */
    public $connectTimeout = 0;

    /**
     * @var int
     */
    public $socketTimeout = 0;

    /**
     * @var string
     */
    public $currentSchema = 'public';

    /**
     * @var array
     */
    public $options = [];

    /**
     * @return SqlConnection
     */
    protected function buildClient()
    {
        SqlDriverManager::install('org.postgresql.Driver');

        if (!$this->host || !$this->port) {
            return null;
        }

        $url = "postgresql://$this->host:$this->port/$this->database";

        Logger::info("Connect to '$url'");

        $this->options['user'] = $this->username;
        $this->options['password'] = $this->password;
        $this->options['loginTimeout'] = $this->loginTimeout;
        $this->options['socketTimeout'] = $this->socketTimeout;
        $this->options['connectTimeout'] = $this->connectTimeout;
        $this->options['currentSchema'] = $this->currentSchema;
        $this->options['useSSL'] = $this->useSSL ? 'true' : 'false';

        return SqlDriverManager::getConnection($url, $this->options);
    }
}