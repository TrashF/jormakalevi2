<?php

require_once __DIR__ . '/../vendor/autoload.php';
abstract class AbstractScript {

	/**
	 * @var array
	 */
	protected $_times = array();

	public abstract function run();

	public function __construct() {
		$this->run();
	}

	/**
	 * Start cheap profiler
	 *
	 * @param null|string $id
	 * @return string
	 */
	protected function _start($id = null) {
		if (is_null($id)) {
			$id = uniqid("", true);
		}
		$this->_times[$id] = microtime(true);
		return $id;
	}

	/**
	 * Stop profiler and return result
	 *
	 * @param $id
	 * @return mixed
	 */
	protected function _stop($id) {
		$result = microtime(true) - $this->_times[$id];
		unset($this->_times[$id]);
		return $result;
	}

}