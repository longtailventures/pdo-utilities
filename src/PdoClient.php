<?php

namespace LongTailVentures;

use Exception;
use PDO;
use PDOException;

class PdoClient
{
    /** @var PDO[] $_connections */
	protected static $_connections;

	protected static $_connectionParams;
	protected static $_errorMode;


	/**
	 * sets up a connection
	 *
	 * @param string $name
	 * The name of the connection
	 *
	 * @param array $connectionParams
	 * A hash whose keys are the following:
	 * - host
	 * - username
	 * - password
     *
     * @param int $errorMode
     * Should be a PDO constant
     * Optional, default is PDO::ERRMODE_SILENT
	 *
     * @throws Exception
     * If $name is empty
	 *
	 * @return bool $isSetup
	 * True if setup, false otherwise
	 */
	public static function setup($name, array $connectionParams, $errorMode = PDO::ERRMODE_SILENT) : bool
	{
		if (empty($name))
			throw new Exception("A name must be specified");

		$connectionId = self::_generateConnectionId($name);

		self::clearConnection($connectionId);

		self::addConnectionParams($name, $connectionParams);
		self::$_errorMode = $errorMode;

		return true;
	}


	/**
	 * adds a set of connection params. Use this function to add a secondary param set in case the first one (as passed
	 * in setup()) fails
	 *
	 * @param string $name
	 * The name of the connection
	 *
	 * @param array $connectionParams
	 * A hash whose keys are the following:
	 * - host
	 * - username
	 * - password
	 * - username
	 *
     * @throws Exception
     * If $connectionParams has required fields missing
	 *
	 * @return boolean $isSetup
	 * True if setup, false otherwise
	 */
	public static function addConnectionParams($name, array $connectionParams) : bool
	{
		$connectionId = self::_generateConnectionId($name);

		if (!array_key_exists($connectionId, self::$_connections))
			throw new Exception("$name not found in connections pool. Use setup()");

		if (!isset($connectionParams['host'], $connectionParams['username'], $connectionParams['password']))
			throw new Exception("Required field missing for connection params");

		if (!is_array(self::$_connectionParams))
			self::$_connectionParams = array();

		if (!isset(self::$_connectionParams[$connectionId]))
			self::$_connectionParams[$connectionId] = array();

		self::$_connectionParams[$connectionId][] = $connectionParams;

		return true;
	}


	/**
     * Retreives the current database (PDO) connection setup by $name. This is a
     * singleton function (i.e. creates connection on demand, stores only copy). If a separate unique db connection is
     * needed manually create one using PDO constructor
     *
     * @param string $name
     * The name of the connection to be retrieved
     *
     * @return PDO connection
     * Null is returned if no matching connection can be round for $name
     */
	public static function getConnection($name) : ?PDO
	{
		$connectionId = self::_generateConnectionId($name);

		if (!array_key_exists($connectionId, self::$_connections))
		    return null;

		if (!array_key_exists($connectionId, self::$_connectionParams))
            return null;

		if (self::$_connections[$connectionId] !== null)
		{
		    // check if connection is active
    		try
    		{
    		    self::$_connections[$connectionId]->query("select 1");
            }
            catch (PDOException $e)
            {
                self::$_connections[$connectionId] = null;
            }
		}

		if (self::$_connections[$connectionId] === null)
		{
			$connectionException = null;
			$isConnectionFound = false;

			$connection = null;
			for ($i = 0; $i < count(self::$_connectionParams[$connectionId]) && !$isConnectionFound; $i++)
			{
				$connectionParams = self::$_connectionParams[$connectionId][$i];
				$dsn = "mysql:dbname={$connectionParams['database']};host={$connectionParams['host']}";
				try
				{
					$connection = new PDO($dsn, $connectionParams['username'], $connectionParams['password']);

					if (self::$_errorMode == PDO::ERRMODE_EXCEPTION)
					   $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

					$isConnectionFound = true;
				}
				catch (PDOException $ex)
				{
				}
			}

			if (!$isConnectionFound)
                return null;

			self::$_connections[$connectionId] = $connection;
		}

		return self::$_connections[$connectionId];
	}


	/**
	 * Determines if a connection specified by $name has been established
     *
     * @param string $name
     * The name of the connection to be retrieved
	 *
	 * @return bool $isConnectionEstablished
	 * True if connection is established, false otherwise
	 */
	public static function isConnectionEstablished($name) : bool
    {
    	$connectionId = self::_generateConnectionId($name);

    	return isset(self::$_connections[$connectionId]);
    }


    /**
     * Determines whether a replica connection is 'ready' (ie. caught up with the master connection)
     *
     * @param PDO $replicaConnection
     *
     * @param int $maxNumberOfSecondsToCheck
     * How many seconds to wait for the replica connection to be caught up with its master connection.
     * Default is 1200 (20 minutes)
     *
     * @param int $secondsBehindMasterThreshold
     * How many seconds behind master is the replica connection is considered 'ready'. Default is 0
     *
     * @return bool $isReady
     */
    public static function isReplicaConnectionReady($replicaConnection,
    						    $maxNumberOfSecondsToCheck = 1200,
    						    $secondsBehindMasterThreshold = 0) : bool
    {
        $numberOfSeconds = 0;

        do
        {
            $query = "show slave status";
            $result = $replicaConnection->query($query);
            $secondsBehindMaster = 0;
            while ($row = $result->fetch(PDO::FETCH_ASSOC))
                $secondsBehindMaster = $row['Seconds_Behind_Master'];

            $isReady = $secondsBehindMaster <= $secondsBehindMasterThreshold;

            if (!$isReady)
            {
                sleep(1);
                $numberOfSeconds++;
            }
        }
        while (!$isReady && $numberOfSeconds <= $maxNumberOfSecondsToCheck);

        return $isReady;
    }


    /**
     * Clears a connection specified by $connectionId
     *
     * @param string $connectionId
     * The id of the connection to be retrieved
     */
    public static function clearConnection($connectionId) : void
    {
		self::$_connections[$connectionId] = null;
		if (isset(self::$_connectionParams[$connectionId]))
		    unset(self::$_connectionParams[$connectionId]);
    }


    /**
     * function to format a database connection id
     *
     * @param string $name
     *
     * @return string $connectionId
     */
    private static function _generateConnectionId($name)
    {
        return $name;
    }
}
