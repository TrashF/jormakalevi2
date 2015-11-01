<?php

namespace jormakalevi;

/**
 * Class User
 * @package jormakalevi
 */
class User extends Document {

	/**
	 * @var array
	 */
	protected $_permissionTypes = array(
		'create',
		'read',
		'update',
		'delete',
	);

	/**
	 * @param string $password
	 */
	public function setPassword($password) {
		$this->_data['password'] = password_hash($password, PASSWORD_DEFAULT);
	}

	/**
	 * @param string $password
	 * @return bool
	 */
	public function checkPassword($password) {
		return password_verify($password, $this->_data['password']);
	}

	/**
	 * @return array
	 */
	public function jsonSerialize() {
		$data = parent::jsonSerialize();
		unset($data['password']);
		return $data;
	}

	/**
	 * @param null|string $type
	 * @return array
	 * @throws \Exception
	 */
	public function getPermissions($type = null) {
		if (!is_null($type)) {
			return $this->_getPermissions($type);
		}
		$permissions = array();
		foreach ($this->_permissionTypes as $type) {
			$permissions = array_merge($permissions, $this->_getPermissions($type));
		}
		return $permissions;
	}

	/**
	 * @param null|string $type
	 * @param string $className
	 * @throws \Exception
	 */
	public function addPermission($type = null, $className) {
		if (is_null($type)) {
			foreach ($this->_permissionTypes as $type) {
				$this->addPermission($type, $className);
			}
		} else {
			if (!in_array($type, $this->_permissionTypes)) {
				throw new \Exception('Unsupported permission type "' . $type . '".');
			}
			$permissions = $this->_getPermissions($type);
			$permissions[] = $className;
			$this->setPermissions($type, $permissions);
		}
	}

	/**
	 * @param null|string $type
	 * @param string $className
	 * @throws \Exception
	 */
	public function removePermission($type = null, $className) {
		if (is_null($type)) {
			foreach ($this->_permissionTypes as $type) {
				$this->removePermission($type, $className);
			}
		} else {
			$permissions = $this->_getPermissions($type);
			if (false !== ($key = array_search($className, $permissions))) {
				unset($permissions[$key]);
				$this->setPermissions($type, $permissions);
			}
		}
	}

	/**
	 * @param string $type
	 * @return array
	 * @throws \Exception
	 */
	protected function _getPermissions($type) {
		if (!in_array($type, $this->_permissionTypes)) {
			throw new \Exception('Unsupported permission type "' . $type . '".');
		}
		return array_filter((array) $this->{$type . 'Permissions'});
	}

	/**
	 * @param null|string $type
	 * @param array $permissions
	 */
	public function setPermissions($type = null, array $permissions = array()) {
		if (is_null($type)) {
			foreach ($permissions as $type => $typePermissions) {
				$this->setPermissions($type, $typePermissions);
			}
		} else {
			$this->{$type . 'Permissions'} = $permissions;
		}
	}

	/**
	 * Checks if user can perform an operation.
	 *
	 * @param string $className
	 * @param string $operation
	 * @return bool
	 */
	public function can($className, $operation) {
		if (Mongo::getInstance()->isAuthDisabled()) {
			return true;
		}
		return in_array($className, $this->getPermissions($operation));
	}

}