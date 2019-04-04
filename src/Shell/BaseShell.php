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

use App\Service\SvidService;
use App\Utility\Log;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Datasource\ModelAwareTrait;
use Cake\Network\Exception\UnauthorizedException;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Class BaseShell
 * Base class of build shells
 *
 * @package App\Shell
 */
class BaseShell extends Shell
{
	use LocatorAwareTrait;

	use ModelAwareTrait;

	/**
	 * @var string Video directory
	 * WWW_ROOT . video_dir_name
	 */
	protected $video_dir = '';

	/**
	 * @var string SVID String
	 */
	protected $svid = '';

	/**
	 * @var string Access token strings
	 */
	protected $access_token = '';

	public function getOptionParser()
	{
		$parser = parent::getOptionParser();

		$parser->addOption(SHELL_OPTION_SVID, [
			'short' => 's',
			'help'  => __('SVID URL of build target video.')
		]);

		if (Configure::read('Auth.enabled')) {
			$parser->addOption(SHELL_OPTION_ACCESS_TOKEN, [
				'short' => 'a',
				'help'  => 'Access token string. Only use Auth plugin setting is enabled.']);
		}
		return $parser;
	}

	/**
	 * Main process of shell
	 * (Arguments takes priority over shell parameters.)
	 *
	 * @param string $tag Task specified name.
	 * @param array  $option Options for API call.
	 * @return int
	 * @throws \Cake\Console\Exception\StopException
	 * @throws UnauthorizedException
	 */
	public function main(string $tag = '', array $option = null)
	{
		if (!empty($tag)) {
			$this->params[SHELL_OPTION_TAG] = $tag;
		}
		if (!empty($option)) {
			$this->params = array_merge($this->params, $option);
		}

		$this->svid = $this->param(SHELL_OPTION_SVID);
		if (empty($this->svid)) {
			Log::error('log_msg_shell_option_not_found', [SHELL_OPTION_SVID, '']);
			$this->abort(__('log_msg_shell_option_not_found'));
		}
		$this->video_dir = SvidService::get_video_path($this->svid);

		if (Configure::read('Auth.enabled')) {
			$this->access_token = $this->param(SHELL_OPTION_ACCESS_TOKEN);
			/*
			if (empty($this->access_token)) {
				throw new UnauthorizedException('Auth token is required');
			}
			*/
		}
		return self::CODE_SUCCESS;
	}

	/**
	 * @param array  $params_json params.json
	 * @param string $search_tag Search tag name
	 * @return array|null Target task on success, NULL on failure.
	 */
	protected static function select_task(array $params_json, string $search_tag)
	{
		foreach ($params_json[PARAMS_TAG_BUILD] as $task) {
			if (self::is_target_task($task, $search_tag)) {
				return $task;
			}
		}
		return null;

	}

	/**
	 * Check $task is target or not
	 * @param array  $task task
	 * @param string $search_tag Search tag name
	 * @return bool Return TRUE if $task is assigned task.
	 */
	private static function is_target_task(array $task, string $search_tag): bool
	{
		return $task[PARAMS_TAG_TAG] === $search_tag;
	}

	/**
	 * Get encode target video list
	 *
	 * @param array        $params_json parameter.json data
	 * @return array Video list
	 */
	protected static function target_videos(array $params_json): array
	{
		return $params_json[PARAMS_TAG_VIDEOS];
	}

}
