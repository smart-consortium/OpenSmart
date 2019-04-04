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

namespace App\Shell\Task;

use App\Service\Model\Process;
use App\Utility\Log;
use Cake\Console\Shell;
use Cake\I18n\Time;
use Cake\ORM\Locator\TableLocator;
use Cake\ORM\TableRegistry;
use Exception;

class WorkerTask extends Shell
{
	public function getOptionParser()
	{
		$parser = parent::getOptionParser();
		$parser->addArgument(SHELL_OPTION_ID, [
			'help' => __('Target job id')
		]);
		$parser->addOption(SHELL_OPTION_STATUS, [
			'short' => 's',
			'help'  => __('Target job status')
		]);
		return $parser;
	}

	public function main()
	{
		$job_id = $this->args[0];
		$state = $this->param(SHELL_OPTION_STATUS);
		$locator = new TableLocator();
		$table = $locator->get(TABLE_NAME_JOBS);
		$job = $table->get($job_id);

		if ($state == JOB_STATUS_FAILED) {
			try {
				Log::warning('[Failed Job] ID => ' . $job_id . '. Because child job is failed.');
				$this->_update_job($job, JOB_STATUS_FAILED);
				$this->_update_video_status($job->video_id, BUILD_STATUS_FAILED);
			} catch (Exception $e) {
				Log::error('[Failed Job] ID => ' . $job_id);
				Log::error($e->getMessage());
				return self::CODE_ERROR;
			}
			return self::CODE_SUCCESS;

		} else {
			Log::info('[Start Job] ID => ' . $job_id);
			$process = new Process($job->command, []);
			list($return_var, $output) = $process->start(false, false);

			try {
				if ($return_var == self::CODE_SUCCESS) {
					$this->_update_job($job, JOB_STATUS_OK);
					Log::info('[Success Job] ID => ' . $job_id);
					return self::CODE_SUCCESS;
				} else {
					$this->_update_job($job, JOB_STATUS_FAILED);
					Log::warning('[Failed Job] ID => ' . $job_id);
					return self::CODE_ERROR;
				}
			} catch (Exception $e) {
				Log::error('[Failed Job] ID => ' . $job_id);
				Log::error($e->getMessage());
				return self::CODE_ERROR;
			}
		}
	}

	/**
	 * @param  \Cake\Datasource\EntityInterface $job
	 * @param int                               $status
	 * @return bool
	 * @throws Exception
	 */
	private function _update_job($job, int $status): bool
	{
		$table = TableRegistry::get(TABLE_NAME_JOBS);
		$job->set('process_id', -1);
		$job->set('end', Time::now());
		$job->set('status', $status);
		if ($table->save($job)) {
			if ($job->id >= 0) {
				return true;
			}
		}
		throw new Exception('Update job status failed');
	}

	/**
	 * @param int $video_id
	 * @param int $status
	 * @return bool
	 * @throws Exception
	 */
	private function _update_video_status(int $video_id, int $status): bool
	{
		$table = TableRegistry::get(TABLE_NAME_VIDEOS);
		$video = $table->get($video_id);
		$video->set('status', $status);
		if ($table->save($video)) {
			if ($video->id > 0) {
				return true;
			}
		}
		throw new Exception('Update video status failed');
	}

}