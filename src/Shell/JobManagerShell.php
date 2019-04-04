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

namespace App\Shell;

use App\Service\ExecuteTrait;
use App\Service\Model\CakeProcess;
use App\Utility\Log;
use Cake\Collection\ExtractTrait;
use Cake\Console\ConsoleIo;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\I18n\Time;
use Cake\Network\Exception\NotImplementedException;
use Exception;

/**
 * Class JobManagerShell
 * @package App\Shell
 * @property \App\Model\Table\VideosTable $Videos
 * @property \App\Model\Table\JobsTable   $Jobs
 */
class JobManagerShell extends Shell
{
	use ExecuteTrait;

	public $tasks = ['Worker'];

	private $wait_time_ms = DEFAULT_WAIT_TIME;

	private $workers_limit = DEFAULT_CONCURRENT_WORKERS;


	public function getOptionParser()
	{
		$parser = parent::getOptionParser();
		$parser->addSubcommand(TASK_NAME_WORKER, [
			'help'   => __('Startup ' . TASK_NAME_WORKER . ' process'),
			'parser' => $this->Worker->getOptionParser()
		]);

		return $parser;
	}

	public function __construct(?ConsoleIo $io = null)
	{
		parent::__construct($io);
		$this->wait_time_ms = Configure::read('System.process_wait');
		$this->workers_limit = Configure::read('System.concurrent_workers_limit');
	}

	public function main()
	{
		$this->loadModel(TABLE_NAME_VIDEOS);
		$this->loadModel(TABLE_NAME_JOBS);

		if ($this->is_runnable()) {
			Log::info(SHELL_NAME_JOB_MANAGER . ': Start up');
			$this->run();
		} else {
			Log::notice(SHELL_NAME_JOB_MANAGER . ': already started.');
		}
	}

	/**
	 * Get JobManager status
	 * @return bool TRUE on alive process, FALSE on dead.
	 */
	public function is_alive(): bool
	{
		return $this->count_manager_process() > 0;
	}

	/**
	 * Get process count of JobManager
	 * @return int
	 */
	public function count_manager_process(): int
	{
		list($output, ) = $this->exec(JOB_MANAGER_PROCESS_CHECK);
		return count($output);
	}

	/**
	 * Get process count of Worker
	 * @return int
	 */
	public function count_processing_workers(): int
	{
		list($output, ) = $this->exec(WORKER_PROCESS_CHECK);
		return count($output);
	}


	public function is_runnable(): bool
	{
		return $this->count_manager_process() > 1 ? CODE_FAILED : CODE_SUCCESS;
	}

	public function start()
	{
		$process = new CakeProcess(SHELL_NAME_JOB_MANAGER, []);
		$process->start(true);
	}

	public function stop()
	{
		//TODO
		throw new NotImplementedException('');
	}

	/**
	 * Get waiting status Job count
	 * @return int
	 */
	public function count_waiting_jobs(): int
	{
		$query = $this->_get_waiting_jobs();
		return $query->count();
	}

	public function run()
	{
		while (true) {
			try {
				if ($this->count_processing_workers() >= $this->workers_limit) {
					sleep($this->wait_time_ms);
				} else {
					$pid = $this->_fetch();
					if ($pid > 0) {
						Log::info(TASK_NAME_WORKER . ' process => ' . $pid . ' started');
					} else {
						sleep($this->wait_time_ms);
					}
				}
			} catch (Exception $ex) {
				Log::error($ex->getMessage());
			}
		}
	}


	/**
	 * @return int
	 * @throws Exception
	 */
	private function _fetch(): int
	{
		$jobs = $this->_get_waiting_jobs();
		if ($jobs->count() <= 0) {
			return -1;
		}
		foreach ($jobs as $job) {

			switch ($this->_check_children_status($job->video_id)) {
				case JOB_STATUS_OK:
					$process = new CakeProcess(SHELL_NAME_JOB_MANAGER . ' ' . TASK_NAME_WORKER . ' ' . $job->id, []);
					list(, $output) = $process->start(true, true);
					$this->_update_job($job, $output[0], JOB_STATUS_PROCESSING);
					return $output[0];
				case JOB_STATUS_FAILED:
					$process = new CakeProcess(SHELL_NAME_JOB_MANAGER . ' ' . TASK_NAME_WORKER . ' ' . $job->id,
					                           [SHELL_OPTION_STATUS => JOB_STATUS_FAILED]);
					list(, $output) = $process->start(true, true);
					$this->_update_job($job, $output[0], JOB_STATUS_PROCESSING);
					return $output[0];
				case JOB_STATUS_WAIT:
					break;
				default:
					break;
			}
		}
		return -1;
	}

	/**
	 * @return \Cake\ORM\Query
	 */
	private function _get_waiting_jobs()
	{
		return $this->Jobs->find()
		                  ->select()
		                  ->where(['status' => BUILD_STATUS_WAITING]);
	}

	/**
	 * @param int $video_id
	 * @return int
	 * @throws Exception
	 */
	private function _check_children_status(int $video_id): int
	{
		$children = $this->Videos->find()
		                         ->join(['table'      => mb_strtolower(TABLE_NAME_RELATIONS),
		                                 'alias'      => 'r',
		                                 'type'       => 'INNER',
		                                 'conditions' => 'r.child_id = ' . TABLE_NAME_VIDEOS . '.id'
		                                ])
		                         ->select()
		                         ->where(['r.video_id' => $video_id])
		                         ->all();
		return $this->__check_children_status($children);
	}

	/**
	 * @param                 $children
	 * @return int
	 * @throws Exception
	 */
	private function __check_children_status($children): int
	{
		foreach ($children as $child) {
			switch ($child->status) {
				case BUILD_STATUS_FAILED:
					return JOB_STATUS_FAILED;
				case BUILD_STATUS_WAITING:
					return JOB_STATUS_WAIT;
				case BUILD_STATUS_BUILDING:
					return JOB_STATUS_WAIT;
				default:
					break;
			}
		}
		return JOB_STATUS_OK;
	}

	/**
	 * @param  \Cake\Datasource\EntityInterface $job
	 * @param  int                              $pid
	 * @param int                               $status
	 * @return bool
	 * @throws Exception
	 */
	private function _update_job($job, int $pid, int $status): bool
	{
		$job->set('process_id', $pid);
		$job->set('start', Time::now());
		$job->set('status', $status);
		if ($this->Jobs->save($job)) {
			if ($job->id >= 0) {
				return CODE_SUCCESS;
			}
		}
		throw new Exception('Update job status failed');
	}
}