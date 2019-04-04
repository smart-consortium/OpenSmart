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

use App\Service\PreBuildService;
use App\Shell\BaseShell;
use App\Utility\Log;
use Cake\Console\Exception\StopException;
use Exception;

/**
 * Class PreBuildTask
 * @package App\Shell\Task
 */
class PreBuildTask extends BaseShell implements IBuildTask
{
	public function getOptionParser()
	{
		$parser = parent::getOptionParser();
		return $parser;
	}

	/**
	 * @param string     $tag
	 * @param array|null $option
	 * @return int
	 * @throws \Exception
	 */
	public function main(string $tag = SHELL_COMMAND_NAME_PRE_BUILD, array $option = null)
	{
		try {
			Log::info('log_msg_start_sub_task', $this->name);
			parent::main($tag, $option);

			$service = new PreBuildService($this->svid);
			$service->main($this->params);
			if (!empty($targets)) {
				$this->params[SHELL_OPTION_TARGETS] = $targets;
			}
			Log::info('log_msg_end_sub_task', [$this->name, self::CODE_SUCCESS]);
			return self::CODE_SUCCESS;
		} catch (StopException $e) {
			throw $e;
		} catch (Exception $e) {
			$this->abort($e->getMessage());
		}
		return self::CODE_ERROR;
	}
}