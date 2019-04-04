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

namespace App\Shell\Task;

use App\Service\EncodeService;
use App\Service\ParameterService;
use App\Shell\BaseShell;
use App\Utility\Log;
use Cake\Console\Exception\StopException;
use Exception;

class EncodeTask extends BaseShell implements IBuildTask
{
	public $tasks = ['Hls'];

	public function getOptionParser()
	{
		$parser = parent::getOptionParser();
		$parser->addOption(SHELL_OPTION_VIDEO, [
			'short'    => 'v',
			'multiple' => true,
			'help'     => __('Target video file path')
		]);

		$parser->addOption(SHELL_OPTION_TAG, [
			'short'    => 't',
			'multiple' => true,
			'help'     => __('Target video file path')
		]);

		$parser->addSubcommand(SHELL_COMMAND_NAME_HLS, [
			'help'   => __('Execute HLS encoding by ffmpeg'),
			'parser' => $this->Hls->getOptionParser()
		]);
		return $parser;
	}

	/**
	 * Do Encode task main
	 *
	 * @see BaseShell::main()
	 * @param string     $tag
	 * @param array|null $option
	 * @return int
	 */
	public function main(string $tag = 'encode', array $option = null)
	{
		try {
			Log::info('log_msg_start_sub_task', $this->name);
			parent::main($tag, $option);

			$p_service = new ParameterService($this->param(SHELL_OPTION_SVID));
			$params_json = $p_service->read();

			$e_service = new EncodeService($this->param(SHELL_OPTION_SVID));
			$videos = self::target_videos($params_json);
			$task = self::select_task($params_json, $this->param(SHELL_OPTION_TAG));

			$result = $e_service->main($videos, $task[PARAMS_TAG_OPTIONS]);
			if (!$result) {
				Log::error('log_msg_end_sub_task', [$this->name, self::CODE_ERROR]);
				$this->abort(__('Sub task {0} failed', $this->name), self::CODE_ERROR);
			} else {
				Log::info('log_msg_end_sub_task', [$this->name, self::CODE_SUCCESS]);
			}
		} catch (StopException $e) {
			throw $e;
		} catch (Exception $e) {
			$this->abort($e->getMessage(), 1);
		}
		return self::CODE_SUCCESS;
	}
}