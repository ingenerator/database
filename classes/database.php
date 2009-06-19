<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Database connection wrapper.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
abstract class Database {

	const SELECT =  1;
	const INSERT =  2;
	const UPDATE =  3;
	const DELETE =  4;

	public static $instances = array();

	public static function instance($name = 'default')
	{
		if ( ! isset(Database::$instances[$name]))
		{
			// Load the configuration for this database group
			$config = Kohana::config('database')->$name;

			if ( ! isset($config['type']))
			{
				throw new Kohana_Exception('Database type not defined in :name configuration',
					array(':name' => $name));
			}

			// Set the driver class name
			$driver = 'Database_'.ucfirst($config['type']);

			// Create the database connection instance
			new $driver($name, $config);
		}

		return Database::$instances[$name];
	}

	/**
	 * @var  string  the last query executed
	 */
	public $last_query;

	// Configuration array
	protected $_config;

	// Raw server connection
	protected $_connection;

	public function __construct($name, array $config)
	{
		// Set the instance name
		$this->_name = $name;

		// Add the instance to the list
		Database::$instances[$name] = $this;

		// Store the config locally
		$this->_config = $config;
	}

	public function __destruct()
	{
		$this->disconnect();
	}

	public function __toString()
	{
		// Return the instance name
		return $this->_name;
	}

	abstract public function connect();

	abstract public function disconnect();

	abstract public function set_charset($charset);

	abstract public function query($type, $sql);

	abstract public function list_tables();

	abstract public function list_columns($table);

	abstract public function escape($value);

	public function quote($value)
	{
		if ($value === NULL)
		{
			return 'NULL';
		}
		elseif ($value === TRUE OR $value === FALSE)
		{
			return $value ? 'TRUE' : 'FALSE';
		}
		elseif (is_array($value))
		{
			return implode(', ', array_map(array($this, __FUNCTION__), $value));
		}
		elseif (is_int($value) OR (is_string($value) AND ctype_digit($value)))
		{
			return (int) $value;
		}
		elseif (is_object($value))
		{
			if ($value instanceof Database_Query)
			{
				return '('.$value.')';
			}
			else
			{
				return (string) $value;
			}
		}

		return '"'.$this->escape($value).'"';
	}

} // End Database_Connection