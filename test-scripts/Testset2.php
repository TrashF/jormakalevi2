<?php

use jormakalevi\Mongo;
use jormakalevi\User;

require_once __DIR__ . '/AbstractScript.php';
class Testset2 extends AbstractScript {

	public function run() {

		// get instance with test database
		$mongo = Mongo::getInstance(array('dbName' => 'jormakaleviTest2'));

		// create user
		/**
		 * @var User $user
		 */
		$user = Mongo::factory('jormakalevi\User', array(
			'username' => 'testset2',
		));

		// give all rights to User class
		$user->addPermission(null, get_class($user));

		// set as active user
		$mongo->setUser($user);

		// create 1000 User objects to database, measure how long it took
		$num = 1000;
		$timerID = $this->_start();
		for ($i = 1; $i <= $num; $i++) {
			$u = $mongo::factory('jormakalevi\User', array(
				'username' => 'testuser_' . $i,
			));
			$u->save();
		}
		$spent = $this->_stop($timerID);
		echo 'Created ' . $num . ' User documents to database in ' . sprintf('%f', $spent) . ' seconds' . PHP_EOL;

		$randomNum = mt_rand(1, $num);
		$randomUser = $mongo::factory('jormakalevi\User')->findOneClass(array(
			'username' => 'testuser_' . $randomNum,
		));
		// uncomment the following line if you want to see user object
		//echo json_encode($randomUser, JSON_PRETTY_PRINT) . PHP_EOL;
		assert(strcmp($randomUser->username, 'testuser_' . $randomNum) === 0, 'Username should match.');

		// drop test database
		$mongo->getDB()->drop();
	}

}
new Testset2;