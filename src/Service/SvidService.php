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


use App\Exception\ProcessTimedOutException;
use App\Utility\Log;
use App\Utility\StringUtility;
use App\Utility\SvidUtility;
use Cake\Core\Configure;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\UnauthorizedException;
use Cake\I18n\Time;
use Cake\Routing\Router;

/**
 * Class SvidService
 * @package App\Service
 * @property \App\Model\Table\VideosTable $Videos
 */
class SvidService extends AppService
{
	use AuthTrait;

	private $process_wait_time_limit;

	private $wait_time_ms;

	public function __construct(string $svid, string $username = '', string $auth_token = '')
	{
		parent::__construct($svid, $username, $auth_token);
		$this->process_wait_time_limit = Configure::read('System.process_time_limit');
		$this->wait_time_ms = Configure::read('System.process_wait');

	}

	/**
	 * Read SVID JSON
	 *
	 * @param bool $encode If TRUE then result is JSON string , FALSE JSON array
	 * @param bool $use_cache TRUE => use params.json file , FALSE => read from DB.
	 * @param bool $wait
	 * @param bool $auth
	 * @return array|string
	 * @throws \App\Exception\FileNotFoundException
	 */
	public function svid(bool $encode = true, bool $use_cache = true, bool $wait = true, $auth = true)
	{
		for ($i = 0; $i < $this->process_wait_time_limit; ++$i) {
			$body = $this->_generate_svid($use_cache, $auth);
			if ($wait && $body[SVID_TAG_STATUS] == false) {
				sleep($this->wait_time_ms);
			} else {
				if ($encode) {
					return json_encode($body,JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
				} else {
					return $body;
				}
			}
		}
		throw new ProcessTimedOutException('Read bundle svid process is timed out : svid = ' . $this->svid);
	}

	/**
	 * Make SVID URL
	 *
	 * @param string|null $username Video upload user's name
	 * @param string      $suffix url suffix character
	 * @return string SVID URL
	 */
	public static function make_svid($username, string $suffix = '')
	{
		$base = SvidUtility::svid_base_url();
		$video = self::_make_video_path($username);
		return $base . $video . $suffix;
	}


	/**
	 * Get real path of video directory
	 *
	 * @param      $svid
	 * @param bool $relative
	 * @return null|string
	 */
	public static function get_video_path($svid, $relative = false)
	{
		if (empty($svid) || !StringUtility::startsWith($svid, SvidUtility::svid_base_url())) {
			Log::error('log_msg_illegal_argument', ['svid', $svid]);
			return null;
		}
		$paths = explode(SVID . DS, $svid, 2);
		return $relative ? DS . $paths[1] . DS : WWW_ROOT . $paths[1] . DS;
	}

	/**
	 * Get Video ID from Videos table.
	 *
	 * @return int Video ID
	 * @throws RecordNotFoundException
	 */
	public function get_video_id(): int
	{
		$entity = $this->Videos->find_by_svid($this->svid);
		if (empty($entity->id)) {
			throw new RecordNotFoundException(__('Video not found : ' . $this->svid));
		}
		return $entity->id;
	}

	/**
	 * Generate SVID JSON
	 *
	 * @param bool $use_cache TRUE => use params.json file , FALSE => read from DB.
	 * @param bool $auth
	 * @return array|string
	 * @throws \App\Exception\FileNotFoundException
	 */
	private function _generate_svid(bool $use_cache = true, bool $auth = true)
	{
		$service = new ParameterService($this->svid);
		$params = $service->read($use_cache);
		if ($params == null || self::_is_processing($params)) {
			$result = [SVID_TAG_STATUS => false];
		} else {
			$caption = $params[PARAMS_TAG_CAPTION];
			$version = $params[PARAMS_TAG_VERSION];
			$video_options = self::_video_options($params);
			$camera = self::_camera_options($params, $auth);

			$result = [SVID_TAG_CAPTION => $caption,
			           SVID_TAG_VERSION => $version,
			           SVID_TAG_VIDEO   => $video_options,
			           SVID_TAG_CAMERA  => $camera,
			           SVID_TAG_STATUS  => true];
		}
		return $result;
	}

	private function _video_options(array $params)
	{
		$size = $params[PARAMS_TAG_DEPLOY][PARAMS_TAG_CAMERA][0][PARAMS_TAG_THUMBNAIL][PARAMS_TAG_SIZE];
		$duration = $params[PARAMS_TAG_DEPLOY][PARAMS_TAG_DURATION];

		return ['HLS'      => $this->__make_hls_url(),
		        'duration' => $duration,
		        'size'     => self::__to_size_array($size)];
	}

	/**
	 * Make HLS URL
	 *
	 * @return string
	 */
	private function __make_hls_url(): string
	{
		return Router::fullBaseUrl() . DS . API_HLS_META_MAIN . '?' . SVID . '=' . $this->svid;
	}

	private static function __to_size_array(string $size)
	{
		list($width, $height) = explode(SIZE_DELIMITER, $size, 2);
		return ['width' => (int)$width, 'height' => (int)$height];
	}

	private function _camera_options(array $params, bool $auth = true)
	{
		$ret = [];
		$cameras = $params[PARAMS_TAG_DEPLOY][PARAMS_TAG_CAMERA];
		foreach ($cameras as $camera) {
			$vtrack = $camera['vtrack'];
			$thumbnail = ['source' => self::__source_url($camera['thumbnail']['source']),
			              'times'  => self::__time_list($camera['thumbnail']['times'], $auth),
			              'size'   => $camera['thumbnail']['size']];

			$still = ['source' => self::__source_url($camera['still']['source']),
			          'times'  => self::__time_list($camera['still']['times'], $auth),
			          'size'   => $camera['still']['size']];

			$ret[] = ['name'      => $camera['name'],
			          'still'     => $still,
			          'thumbnail' => $thumbnail,
			          'vtrack'    => $vtrack];
		}
		return $ret;
	}

	/**
	 * @param string $source
	 * @return string
	 */
	private static function __source_url(string $source): string
	{
		if (filter_var($source, FILTER_VALIDATE_URL)) {
			$url = $source;
		} else {
			$url = Router::fullBaseUrl() . DS . $source;
		}
		return $url;
	}

	private function __time_list($time_list, bool $token = true)
	{
		if (is_array($time_list)) {
			return $time_list;
		}
		if (filter_var($time_list, FILTER_VALIDATE_URL)) {
			$url = $time_list;
		} else {
			$video_dir = self::get_video_path($this->svid, true);
			$url = Router::fullBaseUrl() . $video_dir . $time_list;
		}
		if ($token) {
			return $this->with_auth_token($url);
		} else {
			return $url;
		}
	}

	/**
	 * Make video path.
	 *
	 * @param string $username User name
	 * @return string Video directory path (from VIDEOS_DIR)
	 */
	private static function _make_video_path(string $username = null): string
	{
		$path = [VIDEO_DIR];

		// Directory per user
		if (Configure::read('Auth.enabled')) {
			if (empty($user = $username)) {
				throw new UnauthorizedException('Can not get user name.');
			}
		} else {
			if (empty($user = $username)) {
				$user = Configure::read('Auth.default_user_name');
			}
		}
		$path[] = $user;

		// Directories per posted date time
		$date = Time::now();
		$path[] = $date->format('Y');
		$path[] = $date->format('md');
		$path[] = $date->format('His');
		return implode(DS, $path);
	}

	/**
	 * @param array $params
	 * @return bool
	 */
	private static function _is_processing(array $params): bool
	{
		return empty($params) || !array_key_exists(PARAMS_TAG_DEPLOY, $params);
	}
}