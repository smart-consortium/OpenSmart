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

use App\Exception\InvalidArgumentException;
use App\Service\BundleCameraService;
use App\Service\BundleService;
use App\Service\BundleVideoService;
use App\Service\ParameterService;
use App\Shell\BaseShell;
use App\Utility\Log;
use Cake\Console\Exception\StopException;
use Exception;

class BundleTask extends BaseShell implements IBuildTask
{

	public function getOptionParser()
	{
		$parser = parent::getOptionParser();
		return $parser;
	}

	public function main(string $tag = 'bundle', array $option = null)
	{
		try {
			Log::info('log_msg_start_sub_task', $this->name);
			parent::main($tag, $option);
			$p_service = new ParameterService($this->svid);
			$params_json = $p_service->read();

			$b_service = $this->init_bundle_service($this->param(SHELL_OPTION_CAMERA_MODE));
			$task = self::select_task($params_json, $this->param(SHELL_OPTION_TAG));

			$result = $b_service->main($task[PARAMS_TAG_OPTIONS]);
			if (!$result) {
				Log::error('log_msg_end_sub_task', [$this->name, self::CODE_ERROR]);
				$this->abort(__('Sub task {0} failed', $this->name), self::CODE_ERROR);
			} else {
				Log::info('log_msg_end_sub_task', [$this->name, self::CODE_SUCCESS]);
			}
		} catch (StopException $e) {
			throw $e;
		} catch (Exception $e) {
			$this->abort($e->getMessage());
		}

		return self::CODE_SUCCESS;
	}

	/**
	 * Initialize bundle service instance
	 *
	 * @param int $camera_mode Camera mode
	 * @return BundleService
	 */
	private function init_bundle_service(int $camera_mode): BundleService
	{
		switch ($camera_mode) {
			case CAMERA_MODE_TRICK:
				return new BundleVideoService($this->svid);
			case CAMERA_MODE_MULTI:
				return new BundleCameraService($this->svid);
			default:
				throw new InvalidArgumentException('Invalid bundle mode specified.');
		}
	}
}