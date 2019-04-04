<?php
/**
 * OpenSmart :
 * Copyright (c) Smart Consortium. (https://smart-consortium.org)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author      masahiro ehara <masahiro.ehara@irona.co.jp>
 * @copyright   Copyright (c) Smart Consortium. (https://smart-consortium.org)
 * @link        https://smart-consortium.org OpenSmart Project
 * @since       1.0.0
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Controller;

use App\Exception\InvalidArgumentException;
use App\Model\Entity\Relation;
use App\Model\Entity\Video;
use App\Service\Model\CakeProcess;
use App\Service\Model\PostFileIterator;
use App\Service\ParameterService;
use App\Service\StorageService;
use App\Service\SvidService;
use App\Service\Validation\PostFileValidator;
use App\Utility\FileSystem\Folder;
use App\Utility\Log;
use App\Utility\Server;
use Cake\Event\Event;
use Cake\ORM\Exception\RolledbackTransactionException;
use Cake\ORM\TableRegistry;
use Exception;

/**
 * Videos Controller
 *
 * @property \App\Model\Table\VideosTable $Videos
 *
 * @method \App\Model\Entity\Video[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class VideosController extends AppController
{
	const REQUEST_QUERY_NAME_VIEW = 'view';

	const REQUEST_QUERY_VALUE_VIEW_ALL = 'all';

	public function initialize()
	{
		parent::initialize();
		$this->loadComponent('Security');
	}

	public function beforeFilter(Event $event)
	{
		$this->getEventManager()
		//$this->eventManager()
		     ->off($this->Csrf);
		$this->Security->setConfig('unlockedActions', ['post']);
	}

	/**
	 * Index method
	 *
	 * @return \Cake\Http\Response|void
	 */
	public function index()
	{
		$query = $this->request->getQuery(self::REQUEST_QUERY_NAME_VIEW);
		$this->paginate = [
			'contain' => [TABLE_NAME_USERS, TABLE_NAME_SERVERS]
		];
		if ($query === self::REQUEST_QUERY_VALUE_VIEW_ALL) {
			$videos = $this->paginate($this->Videos, [
				'order' => [
					'Videos.id' => 'desc'
				]
			]);
		} else {
			$videos = $this->paginate($this->Videos, [
				'conditions' => [
					'reference' => 0
				],
				'order'      => [
					'Videos.id' => 'desc'
				]
			]);

		}
		$this->set(compact('videos'));
	}

	public function list()
	{
		$query = $this->request->getQuery(self::REQUEST_QUERY_NAME_VIEW);
		$this->paginate = [
			'contain' => [TABLE_NAME_USERS, TABLE_NAME_SERVERS]
		];
		if ($query === self::REQUEST_QUERY_VALUE_VIEW_ALL) {
			$videos = $this->paginate($this->Videos, [
				'order' => [
					'Videos.id' => 'desc'
				]
			]);
		} else {
			$videos = $this->paginate($this->Videos, [
				'conditions' => [
					'reference' => 0
				],
				'order'      => [
					'Videos.id' => 'desc'
				]
			]);
		}
		$this->viewBuilder()
		     ->setClassName('Json');
		$this->set(compact('videos'));
		$this->set('_serialize', 'videos');
	}


	/**
	 * View method
	 *
	 * @param string|null $id Video id.
	 * @return \Cake\Http\Response|void
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 */
	public function view($id = null)
	{
		$video = $this->Videos->get($id, [
			'contain' => [TABLE_NAME_USERS,
			              TABLE_NAME_SERVERS,
			              TABLE_NAME_PARAMETERS,
			              TABLE_NAME_RELATIONS]
		]);

		//TODO optimize SQL
		$children = [];
		foreach ($video->relations as $relation) {
			$children[] = $this->Videos->get($relation->child_id);
		}

		$this->set('video', $video);
		$this->set('children', $children);
	}

	/**
	 * thumbnail list method
	 *
	 * @param string|null $id Video id.
	 * @return \Cake\Http\Response|void
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 */
	public function thumbnails($id = null)
	{
		$video = $this->Videos->get($id);
		$this->set('video', $video);
	}


	/**
	 * Delete method
	 *
	 * @param string|null $id Video id.
	 * @return \Cake\Http\Response|null Redirects to index.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 */
	public function delete($id = null)
	{
		$this->request->allowMethod(['post', 'delete']);
		$video = $this->Videos->get($id);
		if (Server::is_storage_server_mode()) {

			$table = TableRegistry::get(TABLE_NAME_RELATIONS);
			$connection = $table->getConnection();
			try {
				$connection->begin();
				$result_set = $table->find()
				                    ->where([Relation::VIDEO_ID => $id])
				                    ->all();
				foreach ($result_set->toArray() as $item) {
					$child_id = $item[Relation::CHILD_ID];
					$child = $this->Videos->get($child_id);
					$child->set(Video::REFERENCE, $child->get(Video::REFERENCE) - 1);
					$this->Videos->save($child);
				}

				if ($this->Videos->delete($video)) {
					$dir = new Folder(_WWW_ROOT_ . $video->path);
					if ($dir->delete()) {
						$connection->commit();
						Log::info('Deleted video: ' . $video->svid);
						$this->Flash->success(__('The video has been deleted.'));
					} else {
						throw new RolledbackTransactionException(__('The video directory could not be deleted. Please, try again.'));
					}
				} else {
					throw new RolledbackTransactionException(__('The video record could not be deleted. Please, try again.'));
				}
			} catch (Exception $ex) {
				$connection->rollback();
				$this->Flash->error($ex->getMessage());
			}

			return $this->redirect(['action' => 'index']);
		} else {
			//TODO call api
		}
	}

	/**
	 * Upload video
	 *
	 * @return \Cake\Http\Response|void
	 */
	public function upload()
	{
	}

	/**
	 * Make multi method
	 *
	 * @return \Cake\Http\Response|void
	 */
	public function makeMulti()
	{
		$this->paginate = [
			'contain' => [TABLE_NAME_USERS, TABLE_NAME_SERVERS]
		];
		$videos = $this->paginate(
			$this->Videos->find()
			             ->where(['mode <= 1', 'status = 2'])
			             ->order(['Videos.id' => 'desc'])
		);
		$this->set(compact('videos'));
	}


	/**
	 * Post video file to web server and build if need.
	 * @return \Cake\Http\Response|null
	 * @throws \Exception
	 */
	public function post()
	{
		$this->autoRender = false;

		if (!PostFileValidator::validate()) {
			throw new InvalidArgumentException(__('Invalid post data'));
		}

		$camera_ids = [];
		$cameras = [];
		$iterator = new PostFileIterator();
		$total_cameras = 1;
		while ($iterator->has_next()) {
			$total_videos = 1;
			$video_ids = [];
			$videos = [];
			$index = $iterator->next_index();
			foreach ($iterator->video_types() as $video_type) {
				list($id, $svid) = $this->_build($index, $video_type, $total_videos++);
				$video_ids[] = $id;
				$videos[$video_type] = $svid;
			}

			if (self::_is_make_trick_video($videos)) {
				list($id, $svid) = $this->_build_trick($video_ids, $videos, $total_cameras++);
				$camera_ids[] = $id;
				$cameras[] = $svid;
			} else {
				$camera_ids[] = reset($video_ids);
				$cameras[] = reset($videos);
			}
		}

		if (self::_is_make_multi_camera_video($cameras)) {
			$this->_build_multi_camera($camera_ids, $cameras);
		}

		$this->Flash->success(__('The video has been uploaded.'));
		return $this->redirect('/');
	}

	/**
	 * @return \Cake\Http\Response|null
	 * @throws Exception
	 */
	public function trick()
	{
		$videos = [];
		$video_ids = [];
		foreach (REQUEST_VIDEO_TYPE_LIST as $video_type) {
			$svid = $this->request->getQuery($video_type);
			if ($video_type === REQUEST_KEY_PLAY_FORWARD && empty($svid)) {
				throw new InvalidArgumentException(__('Play forward video is required.'));
			}
			if (!empty($svid)) {
				$video = $this->Videos->find_by_svid($svid);
				$video_ids[] = $video->id;
				$videos[$video_type] = $svid;
			}
		}

		if (count($videos) < 2) {
			throw new InvalidArgumentException(__('Too few arguments to make trick video. Least 2 video type expected.'));
		}

		try {
			Log::info('Make trick video start.');
			$svid = $this->_build_trick($video_ids, $videos);
			if (empty($svid)) {
				throw new Exception(__('Build trick error'));
			}
			return $this->redirect('/');
		} catch (Exception $e) {
			Log::error($e->getMessage());
			throw $e;
		} finally {
			Log::info('Make trick video end.');
		}
	}

	/**
	 * @return \Cake\Http\Response|null
	 * @throws Exception
	 */
	public function multi()
	{
		$camera_svid_list = $this->request->getQuery(REQUEST_KEY_CAMERA);
		if (count($camera_svid_list) < 2) {
			throw new InvalidArgumentException(__('Too few arguments to make multi camera video. Least 2 camera expected.'));
		}
		$camera_ids = [];
		foreach ($camera_svid_list as $camera_svid) {
			$camera_ids[] = $this->Videos->find_by_svid($camera_svid);
		}

		try {
			Log::info('Make multi camera video.');
			$svid = $this->_build_multi_camera($camera_ids, $camera_svid_list);
			if (empty($svid)) {
				throw new Exception(__('Build trick error'));
			}
			return $this->redirect('/');
		} catch (Exception $e) {
			Log::error($e->getMessage());
			throw $e;
		} finally {
			Log::info('Make multi camera video end.');
		}

	}

	/**
	 * Rebuild video
	 *
	 * @param int $index Video ID
	 * @return \Cake\Http\Response|null
	 * @throws Exception
	 */
	public function rebuild(int $index)
	{
		$svid = $this->Videos->get($index)->svid;
		$service = new ParameterService($svid);
		$params = $service->read(true);
		$targets = $params[PARAMS_TAG_BUILD][0][PARAMS_TAG_OPTIONS][PARAMS_TAG_VIDEOS];
		$args = [SHELL_OPTION_SVID        => $svid,
		         SHELL_OPTION_TARGETS     => $targets,
		         SHELL_OPTION_REBUILD     => true,
		         SHELL_OPTION_CAMERA_MODE => CAMERA_MODE_SINGLE];

		if ($this->_register(JOB_NAME_REBUILD, $args) < 0) {
			throw new Exception(__('Register rebuild video job failed.'));
		}
		return $this->redirect('/');
	}

	/**
	 * Build video
	 *
	 * @param int    $index Camera index
	 * @param string $video_type Video type string
	 * @param int    $video_num Total Video type count of camera
	 * @return array Video ID and SVID string
	 * @throws Exception
	 */
	private function _build(int $index, string $video_type, int $video_num = 1): array
	{
		$svid = SvidService::make_svid($this->username, $index . $video_num);
		$service = new StorageService($svid, $this->username);
		list($id, $targets) = $service->post($index, $video_type);

		$args = [SVID                     => $svid,
		         SHELL_OPTION_TARGETS     => $targets,
		         SHELL_OPTION_CAMERA_MODE => CAMERA_MODE_SINGLE];

		if ($this->_register(JOB_NAME_BUILD, $args) < 0) {
			throw new Exception(__('Register build video job failed.'));
		}
		return [$id, $svid];
	}

	/**
	 * Build trick mode video
	 *
	 * @param array $video_ids Video ID array
	 * @param array $videos Key(Video mode) - Value(SVID) array
	 * @param int   $camera_num
	 * @return array Video ID and SVID string
	 * @throws Exception
	 */
	private function _build_trick(array $video_ids, array $videos, int $camera_num = 1): array
	{
		$svid = SvidService::make_svid($this->username, $camera_num . TRICK_CAMERA_SVID_SUFFIX);

		$service = new StorageService($svid, $this->username);
		list($id,) = $service->create(CAMERA_MODE_TRICK, array_values($videos));

		$args = [SHELL_OPTION_SVID        => $svid,
		         SHELL_OPTION_CAMERA_MODE => CAMERA_MODE_TRICK];
		foreach ($videos as $video_type => $target_svid) {
			$args[$video_type] = $target_svid;
		}

		if ($this->_register(JOB_NAME_BUILD, $args, $video_ids) < 0) {
			throw new Exception(__('Register trick video build job failed.'));
		}
		return [$id, $svid];
	}

	/**
	 * Build multi camera mode video
	 *
	 * @param array $camera_ids Video ID array
	 * @param array $cameras Target camera svid list
	 * @return array Video ID and SVID string
	 * @throws Exception
	 */
	private function _build_multi_camera(array $camera_ids, array $cameras): array
	{
		$svid = SvidService::make_svid($this->username, MULTI_CAMERA_SVID_SUFFIX);

		$service = new StorageService($svid, $this->username);
		list($id,) = $service->create(CAMERA_MODE_MULTI, $cameras);

		$args = [SHELL_OPTION_SVID        => $svid,
		         SHELL_OPTION_TARGETS     => $cameras,
		         SHELL_OPTION_CAMERA_MODE => CAMERA_MODE_MULTI];

		if ($this->_register(JOB_NAME_BUILD, $args, $camera_ids) < 0) {
			throw new Exception(__('Register multi camera video build job failed.'));
		}
		return [$id, $svid];
	}

	/**
	 * Register build process
	 *
	 * @param string $job_name Job name
	 * @param array  $command_args Options Command options
	 * @param array  $children_id_list Target video children ID list
	 * @return int Job ID
	 * @throws Exception
	 */
	private function _register(string $job_name, array $command_args, array $children_id_list = []): int
	{
		$process = new CakeProcess(SHELL_COMMAND_NAME_BUILD, $command_args);
		return $process->register($job_name, $command_args[SHELL_OPTION_SVID], $children_id_list);
	}

	/**
	 * If camera has slow or other video type. Create trick video.
	 *
	 * @param array $videos Video SVID's
	 * @return bool
	 */
	private static function _is_make_trick_video(array $videos): bool
	{
		return count($videos) > 1;
	}

	/**
	 * Return TRUE if multi camera mode
	 *
	 * @param array $cameras Camera SVID's
	 * @return bool
	 */
	private static function _is_make_multi_camera_video(array $cameras): bool
	{
		return count($cameras) > 1;
	}

}
