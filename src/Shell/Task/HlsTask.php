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

use App\Service\HlsEncodeService;
use App\Shell\BaseShell;
use App\Utility\Log;
use Cake\Console\Exception\StopException;
use Exception;

/**
 * Class HlsTask
 * Encode video to HLS format.
 * This task is sub task of EncodeTask.
 *
 * @package App\Shell\Task
 */
class HlsTask extends BaseShell implements IBuildTask
{
	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser()
	{
		$parser = parent::getOptionParser();
		$parser->addOption(SHELL_OPTION_INPUT_FILE, [
			'short' => 'i',
			'help'  => __('Encoding target video file name.')
		]);
		$parser->addOption(SHELL_OPTION_OUTPUT_DIR, [
			'short' => 'o',
			'help'  => __('Output directory name.')
		]);
		$parser->addOption(SHELL_OPTION_ENCODE_TYPE, [
			'short'   => 'e',
			'choices' => [ENCODE_TYPE_HLS],
			'help'    => __('Encoding target video file name.')
		]);
		$parser->addOption(SHELL_OPTION_BIT_RATE_VIDEO, [
			'multiple' => true,
			'short'    => 'v',
			'help'     => __('Encoding target video bit rate.')
		]);
		$parser->addOption(SHELL_OPTION_BIT_RATE_AUDIO, [
			'multiple' => true,
			'short'    => 'a',
			'help'     => __('Encoding target video audio rate.')
		]);
		$parser->addOption(SHELL_OPTION_VIDEO_SIZE, [
			'multiple' => true,
			'short'    => 's',
			'help'     => __('Encoding target video size.')
		]);
		return $parser;
	}

	/**
	 * Make HLS
	 *
	 * @param string $tag Command name
	 * @param array  $option Command options
	 * @return int
	 * @throws Exception
	 * @see BaseShell::main()
	 */
	public function main(string $tag = 'ffmpeg', array $option = null)
	{
		try {
			Log::info('log_msg_start_sub_task', $this->name);
			parent::main($tag, $option);
			$service = new HlsEncodeService($this->param(SHELL_OPTION_SVID));
			$result = $service->main($this->params);
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
}