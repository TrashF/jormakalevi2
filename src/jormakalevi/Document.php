<?php

namespace jormakalevi;
use MongoDB\BSON\ObjectID;
use \MongoDB\Collection;

/**
 * Class Document
 * @package jormakalevi
 */
class Document implements \JsonSerializable {

	/**
	 * @var array
	 */
	protected $_data = array();

	/**
	 * @var mixed
	 */
	protected $_defaultValue = array();

	/**
	 * @var string
	 */
	protected $_collectionName = 'Common';

	/**
	 * @param array $data
	 * @param array $options
	 */
	public function __construct(array $data = array(), array $options = array()) {
		$this->_data = $data;
		if (empty($options['noAfterFind'])) {
			$this->_afterFind($options);
		}
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set($key, $value) {
		$methodName = 'set' . ucfirst($key);
		if (method_exists($this, $methodName)) {
			$this->$methodName($value);
		} else {
			$this->_data[$key] = $value;
		}
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function __get($key) {
		$methodName = 'get' . ucfirst($key);
		if (method_exists($this, $methodName)) {
			return $this->$methodName();
		}
		return isset($this->_data[$key]) ? $this->_data[$key] : $this->_defaultValue;
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function __isset($key) {
		return isset($this->_data[$key]);
	}

	/**
	 * @param string $key
	 */
	public function __unset($key) {
		unset($this->_data[$key]);
	}

	/**
	 * @return array
	 */
	public function jsonSerialize() {
		return $this->_data;
	}

	/**
	 * @return ObjectID|null
	 */
	public function getID() {
		// ensure it is always either null or an instance of \MongoId
		if (!isset($this->_data['_id'])) {
			return null;
		}
		if ($this->_data['_id'] instanceof ObjectID) {
			return $this->_data['_id'];
		}
		try {
			$id = new ObjectID($this->_data['_id']);
		} catch (\Exception $e) {
			return null;
		}
		return $id;
	}

	/**
	 * @param array $options
	 * @return bool
	 * @throws Exception_PermissionException
	 */
	public function save(array $options = array()) {
		$operation = empty($this->_data['_id']) ? 'create' : 'update';
		if (!Mongo::getInstance()->getUser()->can(get_called_class(), $operation)) {
			throw new Exception_PermissionException('Permission denied.');
		}
		if (!$this->_beforeSave($options)) {
			return false;
		}

		$this->_data['className'] = get_called_class();
		$id = $this->getID();
		if (!is_null($id)) {
			$this->_data['_id'] = $id;
			$this->_getCollection()->replaceOne(array('_id' => $id), $this->_data);
		} else {
			$this->_getCollection()->insertOne($this->_data);
		}
		return $this->_afterSave($options);
	}

	/**
	 * @param array $options
	 * @return bool
	 * @throws Exception_PermissionException
	 */
	public function delete(array $options = array()) {
		if (!Mongo::getInstance()->getUser()->can(get_called_class(), __FUNCTION__)) {
			throw new Exception_PermissionException('Permission denied.');
		}
		if (!$this->_beforeDelete($options)) {
			return false;
		}

		$id = $this->getID();
		if (is_null($id)) {
			return $this->_afterDelete($options);
		}

		$this->_getCollection()->deleteOne(array('_id' => $this->getID()));
		return $this->_afterDelete($options);
	}

	/**
	 * @param array $options
	 * @return bool
	 */
	protected function _beforeSave(array $options = array()) {
		return true;
	}

	/**
	 * @param array $options
	 * @return bool
	 */
	protected function _afterSave(array $options = array()) {
		return true;
	}

	/**
	 * @param array $options
	 * @return bool
	 */
	protected function _afterFind(array $options = array()) {
		return true;
	}

	/**
	 * @param array $options
	 * @return bool
	 */
	protected function _beforeDelete(array $options = array()) {
		return true;
	}

	/**
	 * @param array $options
	 * @return bool
	 */
	protected function _afterDelete(array $options = array()) {
		return true;
	}

	/**
	 * @return Collection
	 */
	protected function _getCollection() {
		return Mongo::getInstance()->getDB()->{$this->_collectionName};
	}

	/**
	 * @param array $query
	 * @param array $sort
	 * @param null|int $limit
	 * @param null|int $skip
	 * @return array
	 */
	public function find(array $query = array(), array $sort = array(), $limit = null, $skip = null) {
		if (!Mongo::getInstance()->isAuthDisabled()) {
			// TODO: add support for $and and $or queries
			if (isset($query['className'])) {
				if (is_array($query['className']) && !empty($query['className']['$in'])) {
					$newIn = array();
					foreach ($query['className']['$in'] as $className) {
						if (Mongo::getInstance()->getUser()->can($className, 'read')) {
							$newIn[] = $className;
						}
					}
					if (empty($newIn)) {
						return array();
					}
					$query['className']['$in'] = $newIn;
				} elseif (is_string($query['className']) && !Mongo::getInstance()->getUser()->can($query['className'], 'read')) {
					return array();
				}
			} else {
				$query['className'] = array('$in' => Mongo::getInstance()->getUser()->getPermissions('read'));
			}
		}
		$cursor = $this->_getCollection()->find($query, array(
			'sort' => $sort,
			'limit' => $limit,
			'skip' => $skip,
		));
		$results = array();
		foreach ($cursor as $r) {
			$r = $r->getArrayCopy();
			if (empty($r['className'])) {
				// TODO: logging
				continue;
			}
			$results[] = Mongo::factory($r['className'], $r);
		}

		return $results;
	}

	/**
	 * @param array $query
	 * @param array $sort
	 * @param null|int $limit
	 * @param null|int $skip
	 * @return array
	 */
	public function findClass(array $query = array(), array $sort = array(), $limit = null, $skip = null) {
		$query['className'] = get_called_class();
		return $this->find($query, $sort, $limit, $skip);
	}

	/**
	 * @param array $query
	 * @param array $sort
	 * @return null|Document
	 */
	public function findOne(array $query = array(), array $sort = array()) {
		$results = $this->find($query, $sort, 1);
		return empty($results) ? null : $results[0];
	}

	/**
	 * @param array $query
	 * @param array $sort
	 * @return null|Document
	 */
	public function findOneClass(array $query = array(), array $sort = array()) {
		$results = $this->findClass($query, $sort, 1);
		return empty($results) ? null : $results[0];
	}

}