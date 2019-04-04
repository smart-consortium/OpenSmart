<?php

namespace App\Model\Table;

use App\Model\Entity\User;
use App\Utility\Log;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Users Model
 *
 * @property \App\Model\Table\VideosTable|\Cake\ORM\Association\HasMany $Videos
 *
 * @method \App\Model\Entity\User get($primaryKey, $options = [])
 * @method \App\Model\Entity\User newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\User[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\User|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\User patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\User[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\User findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class UsersTable extends Table
{
	/**
	 * Default user ID (Auth plugin is disabled)
	 *
	 * @var string
	 */
	const DEFAULT_USER_ID = 1;

	/**
	 * Initialize method
	 *
	 * @param array $config The configuration for the Table.
	 * @return void
	 */
	public function initialize(array $config)
	{
		parent::initialize($config);

		$this->setTable('users');
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');

		$this->addBehavior('Timestamp');

		$this->hasMany('Videos', [
			'foreignKey' => 'user_id'
		]);
	}

	/**
	 * Default validation rules.
	 *
	 * @param \Cake\Validation\Validator $validator Validator instance.
	 * @return \Cake\Validation\Validator
	 */
	public function validationDefault(Validator $validator)
	{
		$validator
			->integer('id')
			->allowEmpty('id', 'create');

		$validator
			->scalar('name')
			->maxLength('name', 50)
			->requirePresence('name', 'create')
			->notEmpty('name')
			->add('name', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);


		$validator
			->scalar('first_name')
			->maxLength('first_name', 50)
			->requirePresence('first_name', 'create')
			->notEmpty('first_name');

		$validator
			->scalar('family_name')
			->maxLength('family_name', 50)
			->requirePresence('family_name', 'create')
			->notEmpty('family_name');

		$validator
			->email('email')
			->requirePresence('email', 'create')
			->notEmpty('email');

		$validator
			->scalar('password')
			->maxLength('password', 50)
			->requirePresence('password', 'create')
			->notEmpty('password');

		return $validator;
	}

	/**
	 * Returns a rules checker object that will be used for validating
	 * application integrity.
	 *
	 * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Cake\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker $rules)
	{
		$rules->add($rules->isUnique(['email']));

		return $rules;
	}

	/**
	 * Get User id from user table.
	 * @param string $user_name User name
	 * @return int User ID
	 */
	public function get_id(string $user_name = null): int
	{
		if (empty($user_name)) {
			return self::DEFAULT_USER_ID;
		}
		return $this->find()
		            ->select([User::ID])
		            ->where([User::USERNAME => $user_name])
		            ->first()
			->id;
	}
}
