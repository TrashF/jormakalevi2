<?php

use jormakalevi\Mongo;
use jormakalevi\User;

require_once __DIR__ . '/AbstractScript.php';
class Testset1 extends AbstractScript {

	public function run() {

		// get instance with test database
		$mongo = Mongo::getInstance(array('dbName' => 'jormakaleviTest1'));

		// create user
		/**
		 * @var User $user
		 */
		$user = Mongo::factory('jormakalevi\User', array(
			'username' => 'testset1',
		));

		// give all rights to User class
		$user->addPermission(null, get_class($user));

		// set as active user
		$mongo->setUser($user);

		// try to find existing, saved user - using permissions
		$existingUser = $mongo::factory('jormakalevi\User')->findOneClass(array(
			'username' => 'testset1',
		));
		if (!is_null($existingUser)) {
			$user = $existingUser;
		}

		// test that User found/created is an instance of User
		assert($user instanceof User, 'User is not an instance of user.');

		// user should be able to save users, because both create and update permissions are set
		assert($user->save(), 'Saving user failed.');

		// test if user is able to eat User documents
		try {
			$userCanEatUsers = $user->can('jormakalevi\User', 'eat');
		} catch (\Exception $e) {
			$userCanEatUsers = false;
		}
		assert($userCanEatUsers === false, 'Users cannot eat users (or probably not use any other foobar permission types)');

		// test that user is not able to create Documents, because no such permission has been given
		assert(false === $user->can('jormakalevi\Document', 'create'), 'User should not be able to create this class.');

		// drop test database
		$mongo->getDB()->drop();
	}

}
new Testset1;