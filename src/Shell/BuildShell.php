<?php
/**
 * OpenSmart :
 * Copyright (c) Smart Consortium. (https://smart-consortium.org)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author        masahiro ehara <masahiro.ehara@irona.co.jp>
 * @copyright     Copyright (c) Smart Consortium. (https://smart-consortium.org)
 * @link          https://smart-consortium.org OpenSmart Project
 * @since         0.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Shell;

use App\Model\Entity\Video;
use App\Service\ParameterService;
use App\Utility\Log;
use Cake\Console\Exception\StopException;
use Exception;

/**
 * Class BuildShell
 * @package App\Shell
 *
 * @property \App\Model\Table\VideosTable $Videos
 */
class BuildShell extends BaseShell
{
	public $tasks = ['PreBuild', 'Encode', 'Deploy', 'Bundle'];

	public function getOptionParser()
	{
		$parser = parent::getOptionParser();
		$parser->addOption(SHELL_OPTION_TARGETS, [
			'short'    => 't',
			'multiple' => true,
			'help'     => __('Target video file names or SVID list')
		]);

		$parser->addOption(SHELL_OPTION_TARGET_PLAY_FORWARD, [
			'help' => __('Play forward mode video\'s svid')
		]);

		$parser->addOption(SHELL_OPTION_TARGET_PLAY_REVERSE, [
			'help' => __('Play reverse mode video\'s svid')
		]);

		$parser->addOption(SHELL_OPTION_TARGET_SLOW_FORWARD, [
			'help' => __('Slow forward mode video\'s svid')
		]);

		$parser->addOption(SHELL_OPTION_TARGET_SLOW_REVERSE, [
			'help' => __('Slow reverse mode video\'s svid')
		]);

		$parser->addOption(SHELL_OPTION_CAMERA_MODE, [
			'short'   => 'm',
			'choices' => [CAMERA_MODE_SINGLE, CAMERA_MODE_TRICK, CAMERA_MODE_MULTI],
			'help'    => __('Target video camera mode.')
		]);

		$parser->addOption(SHELL_OPTION_EMAIL, [
			'multiple' => true,
			'help'     => __('Send notification by e-mail(NOT IMPLEMENTS)')
		]);

		$parser->addOption(SHELL_OPTION_REBUILD, [
			'help' => __('Rebuild video')
		]);

		$parser->addOption(SHELL_OPTION_NORMALIZE, [
			'help' => __('Normalize video encode')
		]);

		$parser->addOption(SHELL_OPTION_EMAIL, [
			'multiple' => true,
			'help'     => __('Send notification by e-mail(NOT IMPLEMENTS)')
		]);

		//Sub Command
		$parser->addSubcommand(SHELL_COMMAND_NAME_PRE_BUILD, [
			'help'   => __('Execute pre build task'),
			'parser' => $this->PreBuild->getOptionParser()
		]);

		$parser->addSubcommand(SHELL_COMMAND_NAME_ENCODE, [
			'help'   => __('Execute encode task'),
			'parser' => $this->Encode->getOptionParser()
		]);

		$parser->addSubcommand(SHELL_COMMAND_NAME_DEPLOY, [
			'help'   => __('Execute deploy task'),
			'parser' => $this->Deploy->getOptionParser()
		]);

		$parser->addSubcommand(SHELL_COMMAND_NAME_BUNDLE, [
			'help'   => __('Execute bundle task'),
			'parser' => $this->Bundle->getOptionParser()
		]);
		return $parser;
	}


	/**
	 * Batch build entrance
	 *
	 * @see BaseShell::main()
	 * @param string     $tag
	 * @param array|null $option
	 * @return int
	 */
	public function main(string $tag = 'build', array $option = null)
	{
		$this->loadModel(TABLE_NAME_VIDEOS);

		try {
			Log::info('log_msg_start_shell', __CLASS__);
			parent::main($tag, $option);
			Log::debug('Build target => ' . $this->svid);

			$this->_update_status($this->svid, BUILD_STATUS_BUILDING);

			$this->PreBuild->main(SHELL_COMMAND_NAME_PRE_BUILD, []);

			if (!array_key_exists(SHELL_OPTION_REBUILD, $this->params)) {
				$service = new ParameterService($this->param(SHELL_OPTION_SVID));
				$service->add($this->params);
			}

			$this->_do_sub_tasks();

			$this->_update_status($this->svid, BUILD_STATUS_SUCCESS);
			Log::info('log_msg_end_shell', [__CLASS__, BUILD_STATUS_SUCCESS]);

			return self::CODE_SUCCESS;
		} catch (Exception $ex) {
			Log::error($ex->getMessage());
			Log::error('log_msg_end_shell', [__CLASS__, BUILD_STATUS_FAILED]);
			try {
				$this->_update_status($this->svid, BUILD_STATUS_FAILED);
			} catch (Exception $ex) {
				// pass
			}
			return self::CODE_ERROR;
		}
	}

	/**
	 * Execute all sub tasks
	 * @throws StopException
	 */
	private function _do_sub_tasks(): void
	{
		$service = new ParameterService($this->svid);
		$params_json = $service->read();

		foreach ($params_json[PARAMS_TAG_BUILD] as $task) {
			$command = $task[PARAMS_TAG_COMMAND];
			$sub = $this->_sub_task($command);

			if ($sub == null) {
				$this->abort(__('Illegal command : {0}', [$command]));
			}

			$result = $sub->main($task[PARAMS_TAG_TAG], $task[PARAMS_TAG_OPTIONS]);
			if ($result['return'] != 0) {
				$this->abort(__('Task {0} is failed.', [$command]));
			}
		}
	}


	/**
	 * Resolve sub task from task name.
	 *
	 * @param string $name Task name.
	 * @return Object|null Returns Shell object on success. NULL on failure.
	 */
	private function _sub_task(string $name)
	{
		if (empty($name)) {
			return null;
		}

		$_name = '';
		if (empty($parts = explode('_', $name))) {
			$_name = ucfirst($name);
		} else {
			foreach ($parts as $p) {
				$_name .= ucfirst($p);
			}
		}

		if (array_key_exists($_name, $this->tasks)) {
			return $this->$_name;
		}
		return null;
	}

	/**
	 * Update build status
	 *
	 * @param string $svid SVID
	 * @param int    $status Status
	 * @return bool TRUE on success, FALSE on failure.
	 */
	private function _update_status(string $svid, int $status): bool
	{
		$entity = $this->Videos->find_by_svid($svid);
		$entity->set(Video::STATUS, $status);
		if ($this->Videos->save($entity)) {
			if ($entity->id > 0) {
				return CODE_SUCCESS;
			}
		}
		$this->abort(__('Update table ' . TABLE_NAME_VIDEOS . ' failed.'));
	}
}
