<?php

namespace jormakalevi;

/**
 * Class Mongo
 * @package jormakalevi
 */
class Mongo {

	/**
	 * @var Mongo
	 */
	protected static $_instance;

	/**
	 * @var User|null
	 */
	protected $_user;

	/**
	 * @var \MongoDB
	 */
	protected $_db;

	/**
	 * @var bool
	 */
	protected $_authDisabled = false;

	/**
	 * @param array $options
	 * @return Mongo
	 */
	public static function getInstance(array $options = array()) {
		if (is_null(Mongo::$_instance)) {
			Mongo::$_instance = new Mongo($options);
		}
		return Mongo::$_instance;
	}

	public function __clone() {}

	/**
	 * @param array $options
	 */
	public function __construct(array $options = array()) {
		if (isset($options['db']) && $options['db'] instanceof \MongoDB) {
			$this->_db = $options['db'];
		} else {
			if (!empty($options['client']) && $options['client'] instanceof \MongoClient) {
				$connection = $options['client'];
			} elseif (!empty($options['serverString'])) {
				$connection = new \MongoClient($options['serverString']);
			} else {
				$connection = new \MongoClient();
			}
			$databaseName = !empty($options['dbName']) ? $options['dbName'] : 'jormakalevi';
			$this->_db = $connection->selectDB($databaseName);
		}
	}

	/**
	 * @return \MongoDB
	 */
	public function getDB() {
		return $this->_db;
	}

	/**
	 * @return User
	 */
	public function getUser() {
		if (is_null($this->_user)) {
			$this->_user = new User(array(
				'username' => 'anonymous',
			));
		}
		return $this->_user;
	}

	/**
	 * @param User $user
	 */
	public function setUser(User $user) {
		$this->_user = $user;
	}

	/**
	 * @return bool
	 */
	public function isAuthDisabled() {
		return $this->_authDisabled;
	}

	/**
	 * @void
	 */
	public function disableAuth() {
		$this->_authDisabled = true;
	}

	/**
	 * @void
	 */
	public function enableAuth() {
		$this->_authDisabled = false;
	}

	/**
	 * @param string $className
	 * @param array $data
	 * @param array $options
	 * @return Document|mixed
	 * @throws \Exception
	 */
	public static function factory($className = '', array $data = array(), array $options = array()) {
		if (empty($className)) {
			$className = 'jormakalevi\Document';
		}
		if (!class_exists($className)) {
			throw new \Exception('Class not found: "' . $className . '".');
		}
		return new $className($data, $options);
	}

}