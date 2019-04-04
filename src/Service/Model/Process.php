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

namespace App\Service\Model;

use App\Service\ExecuteTrait;
use App\Service\SvidService;
use App\Shell\JobManagerShell;
use App\Utility\Log;
use App\Utility\StringUtility;
use Cake\I18n\Time;
use Cake\ORM\Exception\RolledbackTransactionException;
use Cake\ORM\TableRegistry;
use Exception;

class Process
{
	use ExecuteTrait;

	/**
	 * Process ID
	 * @var array string
	 */
	private $pid = -1;

	/**
	 * @var string Execute command
	 */
	protected $command = '';

	/**
	 * Process constructor.
	 * @param string $command
	 * @param array  $args
	 */
	public function __construct(string $command, array $args)
	{
		if (empty($args)) {
			$this->command = $command;
		} else {
			$this->command = vsprintf($command, $args);
		}
	}

	/**
	 * Start command.
	 * Wrapper of PHP 'exec'.
	 *
	 * @param bool $background TRUE if command execute as background process.
	 * @param bool $return_pid TRUE if use execute process id.
	 * @return array return_var and command output array.
	 */
	public function start(bool $background = false, bool $return_pid = true): array
	{
		$this->pid = getmypid();

		$command = $this->_adjustCommand($this->command, $background, $return_pid);
		list($output, $return_var) = $this->exec($command);
		return [$return_var, $output];
	}

	/**
	 * Stop process
	 *
	 * @param bool $wait
	 * @return bool
	 */
	public function stop(bool $wait): bool
	{
		// TODO kill processes;
		return false;
	}

	/**
	 * Set prefix / suffix of shell command for background execute.
	 *
	 * @param string $command shell command string.
	 * @param bool   $background TRUE if background execute.
	 * @param bool   $return_pid TRUE if use execute process ID.
	 * @return string adjusted shell command string
	 */
	private function _adjustCommand(string $command, bool $background = false, bool $return_pid = true): string
	{
		if ($background) {
			if (!StringUtility::startsWith($command, BACKGROUND_EXEC_PREFIX)) {
				$command = BACKGROUND_EXEC_PREFIX . $command;
			}

			if (!StringUtility::endsWith($command, BACKGROUND_EXEC_SUFFIX)) {
				$command .= BACKGROUND_EXEC_SUFFIX;
			}
		}
		if ($return_pid && !strpos($command, BACKGROUND_EXEC_GET_PID)) {
			$command .= BACKGROUND_EXEC_GET_PID;
		}
		Log::debug('log_msg_exec_shell_command', $command, 1);
		return $command;
	}

	/**
	 * @param string $name Job name
	 * @param string $svid Target
	 * @param array  $children Target children id list
	 * @return int Registered job ID
	 * @throws Exception
	 */
	public function register(string $name, string $svid, array $children = []): int
	{
		$table = TableRegistry::get(TABLE_NAME_JOBS);
		$connection = $table->getConnection();
		try {
			$connection->begin();

			$service = new SvidService($svid);

			$entity = $table->newEntity();
			$entity->name = $name;
			$entity->video_id = $service->get_video_id();
			$entity->command = $this->command;
			$entity->process_id = -1;
			$entity->status = BUILD_STATUS_WAITING;
			$entity->start = Time::now();
			if ($table->save($entity)) {
				if ($entity->id < 0) {
					throw new RolledbackTransactionException('Register ' . $name . ' job failed.');
				}
			}
			$this->_register_relations($entity->video_id, $children);

			$connection->commit();
			// start up job manager
			$manager = new JobManagerShell();
			if($manager->is_runnable()){
				$process = new CakeProcess('jobManager',[]);
				$process->start(true);
			}
			return $entity->id;
		} catch (Exception $e) {
			$connection->rollback();
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * @param int   $parent_id
	 * @param array $children
	 * @throws Exception
	 */
	private function _register_relations(int $parent_id, array $children): void
	{
		$table = TableRegistry::get(TABLE_NAME_RELATIONS);
		try {
			for ($i = 0; $i < count($children); $i++) {
				$entity = $table->newEntity();
				$entity->video_id = $parent_id;
				$entity->child_id = $children[$i];
				if ($table->save($entity)) {
					if ($entity->id < 0) {
						throw new RolledbackTransactionException('Register child video id failed.');
					}
				}
			}
		} catch (Exception $ex) {
			throw $ex;
		}
	}

}