
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/**
 * Mobage Redis Caching Class 
 *
 * @package		Mobage
 * @subpackage	Libraries
 * @category	Core
 * @author		mobage platformDev Team
 * @link		
 */


class MG_Cache_redis extends MG_Driver {

	private $_redis;	// Holds the memcached object

	protected $_default_conf 	= array(
			'host' => '127.0.0.1',
			'password' => NULL,
			'port' => 6379,
			'timeout' => 0);

	
	// ------------------------------------------------------------------------

	/**
	 * Setup Redis config and connection
	 *
	 * Loads Redis config file if present. Will halt execution
	 * if a Redis connection can't be established.
	 *
	 * @return	bool
	 * @see		Redis::connect()
	 */
	protected function _setup_redis()
	{
		$config = array();
		$MG =& get_instance();

		if ($MG->config->load('redis', TRUE))
		{
			$config = $MG->config->item('redis');
		}

		$config = array_merge($this->_default_conf, $config['redis']);
		$this->_redis = new Redis();

		try
		{
			$this->_redis->connect($config['host'], $config['port']);
		}
		catch (RedisException $e)
		{
			show_error('Redis connection refused. ' . $e->getMessage());
		}

		if (isset($config['password']))
		{
			$this->_redis->auth($config['password']);
		}
	}
	// ------------------------------------------------------------------------

	/**
	 * Get cache
	 *
	 * @param	string	Cache key identifier
	 * @return	mixed
	 */
	public function get($key)
	{
		return $this->_redis->get($key);
	}


	// ------------------------------------------------------------------------

	/**
	 * Save cache
	 *
	 * @param	string	Cache key identifier
	 * @param	mixed	Data to save
	 * @param	int	Time to live
	 * @return	bool
	 */
	public function save($key, $value, $ttl = NULL)
	{
		return ($ttl)
			? $this->_redis->setex($key, $ttl, $value)
			: $this->_redis->set($key, $value);
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete from cache
	 *
	 * @param	string	Cache key
	 * @return	bool
	 */
	public function delete($key)
	{
		return ($this->_redis->delete($key) === 1);
	}

	// ------------------------------------------------------------------------

	/**
	 * Clean cache
	 *
	 * @return	bool
	 * @see		Redis::flushDB()
	 */
	public function clean()
	{
		return $this->_redis->flushDB();
	}

	// ------------------------------------------------------------------------

	/**
	 * Get cache driver info
	 *
	 * @param	string	Not supported in Redis.
	 *			Only included in order to offer a
	 *			consistent cache API.
	 * @return	array
	 * @see		Redis::info()
	 */
	public function cache_info($type = NULL)
	{
		return $this->_redis->info();
	}

	// ------------------------------------------------------------------------

	/**
	 * Check if the key value exists
	 *
	 * @param	string	Cache key identifier
	 * @param	mixed	Data to save

	 * @return	bool
	 * @see		Redis::sismember()
	 */
	public function cache_sismember($key, $value)
	{
		return $this->_redis->sIsMember($key, $value);
	}


	// ------------------------------------------------------------------------

	/**
	 * Get cache metadata
	 *
	 * @param	string	Cache key
	 * @return	array
	 */
	public function get_metadata($key)
	{
		$value = $this->get($key);

		if ($value)
		{
			return array(
					'expire' => time() + $this->_redis->ttl($key),
					'data' => $value
					);
		}

		return FALSE;
	}



	// ------------------------------------------------------------------------

	/**
	 * Check if Redis driver is supported
	 *
	 * @return	bool
	 */
	public function is_supported()
	{
#		if (extension_loaded('redis'))
#		{
			$this->_setup_redis();
			return TRUE;
#		}
#		else
#		{
#			log_message('error', 'The Redis extension must be loaded to use Redis cache.');
#			return FALSE;
#		}
	}
}
