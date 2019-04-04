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

namespace App\Service;


use App\Utility\Log;

trait ExecuteTrait
{
	//TODO get my pid and execute task pid
	/**
	 * @param string $command
	 * @return array
	 */
	public function exec(string $command): array
	{
		Log::debug($command);
		$start = microtime(true);
		exec($command, $output, $result);
		$end = microtime(true);
		$exec_time = Log::processing_time_ms($start, $end);
		Log::debug('log_msg_exec_time_ms', $exec_time, 1);
		Log::debug('log_msg_exec_shell_returns', $result, 1);
		Log::debug('log_msg_exec_shell_output',
		           json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 1);
		return [$output, $result];
	}

}