<?php

namespace App\Model\Table;

use App\Model\Entity\Video;
use App\Service\SvidService;
use App\Utility\Log;
use Cake\ORM\Exception\RolledbackTransactionException;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Routing\Router;
use Cake\Validation\Validator;
use Exception;

/**
 * Videos Model
 *
 * @property \App\Model\Table\UsersTable|\Cake\ORM\Association\BelongsTo    $Users
 * @property \App\Model\Table\ServersTable|\Cake\ORM\Association\BelongsTo  $Servers
 * @property \App\Model\Table\ParametersTable|\Cake\ORM\Association\HasMany $Parameters
 *
 * @method \App\Model\Entity\Video get($primaryKey, $options = [])
 * @method \App\Model\Entity\Video newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Video[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Video|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Video patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Video[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Video findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class VideosTable extends Table
{
	/**
	 * Initialize method
	 *
	 * @param array $config The configuration for the Table.
	 * @return void
	 */
	public function initialize(array $config)
	{
		parent::initialize($config);

		$this->setTable('videos');
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');

		$this->addBehavior('Timestamp');

		$this->belongsTo('Users', [
			'foreignKey' => 'user_id',
			'joinType'   => 'INNER'
		]);
		$this->belongsTo('Servers', [
			'foreignKey' => 'server_id',
			'joinType'   => 'INNER'
		]);
		$this->hasMany('Parameters', [
			'foreignKey' => 'video_id',
			'dependent'  => true
		]);
		$this->hasMany('Relations', [
			'foreignKey' => 'video_id',
			'joinType'   => 'INNER'
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
			->scalar('svid')
			->maxLength('svid', 511)
			->requirePresence('svid', 'create')
			->notEmpty('svid')
			->add('svid', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

		$validator
			->scalar('caption')
			->maxLength('caption', 255);

		$validator
			->scalar('path')
			->maxLength('path', 255)
			->requirePresence('path', 'create')
			->notEmpty('path');

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
		$rules->add($rules->isUnique(['svid']));
		$rules->add($rules->existsIn(['user_id'], 'Users'));
		$rules->add($rules->existsIn(['server_id'], 'Servers'));

		return $rules;
	}

	/**
	 * Find video entity by SVID
	 *
	 * @param $svid string SVID
	 * @return array|\Cake\Datasource\EntityInterface|null
	 */
	public function find_by_svid($svid)
	{
		return $this->find()
		            ->where([Video::SVID => $svid])
		            ->first();
	}

	/**
	 * Add new video to Videos table
	 *
	 * @param int    $mode Video mode
	 * @param string $username
	 * @param string $svid
	 * @param array  $children_svid
	 * @param array  $options
	 * @return int Video ID
	 * @throws Exception
	 */
	public function add(int $mode, string $username, string $svid, array $children_svid = [], array $options = []): int
	{
		$connection = $this->getConnection();
		try {
			$connection->begin();
			$entity = $this->newEntity();
			$entity->svid = $svid;
			$entity->server_id = $this->Servers->get_id(Router::fullBaseUrl());
			$entity->caption = array_key_exists('caption', $options) && !empty($options['caption']) ? $options['caption'] : $svid;
			$entity->path = SvidService::get_video_path($svid, true);
			$entity->user_id = $this->Users->get_id($username);
			$entity->mode = $mode;
			$entity->reference = 0;
			$entity->duration = 0;
			$entity->status = BUILD_STATUS_WAITING;

			if ($this->save($entity)) {
				foreach ($children_svid as $child_svid) {
					$child = $this->find()
					              ->select()
					              ->where(['svid' => $child_svid])
					              ->first();
					$child->set('reference', $child->get('reference') + 1);
					if (!$this->save($child)) {
						throw new RolledbackTransactionException('Save reference failed : ' . $children_svid);
					}
				}
				$connection->commit();
				return $entity->id;
			}
			throw new RolledbackTransactionException('Save new video failed : ' . $svid);
		} catch (Exception $ex) {
			Log::error($ex->getMessage());
			$connection->rollback();
			throw $ex;
		}
	}
}
