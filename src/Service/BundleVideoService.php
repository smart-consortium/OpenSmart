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

use App\Exception\FileNotFoundException;
use App\Utility\ArrayUtility;
use App\Utility\FileSystem\File;
use App\Utility\FileSystem\Folder;
use App\Utility\HlsUtility;
use App\Utility\Log;
use App\Utility\ParamsJsonUtility;
use App\Utility\StringUtility;
use Cake\Routing\Router;
use Exception;

/**
 * Class BundleVideoService
 * Bundle multiplex videos to single trick video
 *
 * @package App\Service
 */
class BundleVideoService extends BundleService
{
	use AuthTrait;

	protected function make_preview(array $camera_options): bool
	{
		$num = 0;
		foreach ($camera_options[0][PARAMS_TAG_VTRACK] as $index => $svid) {
			$service = new ImageService($svid);
			$service->copy_preview_image($this->preview_dir, $num++);
		}
		Log::debug($camera_options);
		return CODE_SUCCESS;
	}

	/**
	 * @param array $camera_options
	 * @return bool
	 * @throws Exception
	 */
	protected function make_time_list(array $camera_options): bool
	{
		$duration = -1;
		$slow_duration = -1;
		$time_list = [];
		$time_forward = [];
		$still_url = '';
		$thumbnail_url = '';
		$camera_option = $camera_options[0];
		if (array_key_exists(PARAMS_TAG_PLAY_FORWARD, $camera_option[PARAMS_TAG_VTRACK])) {
			$service = new SvidService($camera_option[PARAMS_TAG_VTRACK][PARAMS_TAG_PLAY_FORWARD]);
			$svid = $service->svid(false, true, true, false);
			$times = $svid[PARAMS_TAG_CAMERA][0][PARAMS_TAG_STILL][PARAMS_TAG_TIMES];
			if (is_array($times)) {
				$time_list = $times;
				//create time_list.json file
			} else {
				// copy time_list.json file and read json contents.
				$contents = file_get_contents($this->with_auth_token($times));
				$time_list = json_decode($contents);
			}
			$duration = $svid[PARAMS_TAG_CAMERA][0][PARAMS_TAG_VTRACK][PARAMS_TAG_duration];
		}
		if (empty($time_list)) {
			throw new Exception('Can\'t read time_list.json');
		}
		$time_list_file = new File($this->video_dir . TIME_LIST_JSON_FILE_NAME);
		$time_list_file->write_as_json($time_list);


		if ($duration < 0) {
			throw new Exception('Can\'t read play_forward duration');
		}

		if (array_key_exists(PARAMS_TAG_SLOW_FORWARD, $camera_option[PARAMS_TAG_VTRACK])) {
			$service = new SvidService($camera_option[PARAMS_TAG_VTRACK][PARAMS_TAG_SLOW_FORWARD]);
			$svid = $service->svid(false, true, true, false);
			$camera = $svid[PARAMS_TAG_CAMERA][0];
			$still = $camera[PARAMS_TAG_STILL];
			$thumbnail = $camera[PARAMS_TAG_THUMBNAIL];
			$still_url = $still[PARAMS_TAG_SOURCE];
			$thumbnail_url = $thumbnail[PARAMS_TAG_SOURCE];
			$slow_duration = $camera[PARAMS_TAG_VTRACK][PARAMS_TAG_duration];
			if ($slow_duration < 0) {
				throw new Exception('Can\'t read slow_forward duration');
			}
			if (empty($still_url) || empty($thumbnail_url)) {
				throw new Exception('Can\'t read still/thumbnail base url');
			}

			$ratio = round($slow_duration / $duration);

			for ($i = 0; $i < count($time_list); $i++) {
				$forward_time = $time_list[$i] * $ratio;
				$time_forward[$time_list[$i]] = [PARAMS_TAG_STILL     => $still_url . $forward_time . '.' . DEFAULT_IMG_EXT,
				                                 PARAMS_TAG_THUMBNAIL => $thumbnail_url . $forward_time . '.' . DEFAULT_IMG_EXT];
			}

			$time_forward_file = new File($this->video_dir . TIME_FORWARD_JSON_FILE_NAME);
			$time_forward_file->write_as_json($time_forward);
		}
		return true;
	}

	/**
	 * @param mixed $params_json
	 * @param array $camera_options
	 * @return array
	 */
	protected function update($params_json, array $camera_options): array
	{
		$total_duration = 0;
		$dst_duration = 0;
		foreach ($camera_options as $index => $option) {
			$params_json[PARAMS_TAG_DEPLOY][PARAMS_TAG_CAMERA][0][PARAMS_TAG_NAME] = $option[PARAMS_TAG_NAME];

			foreach (PARAMS_TAG_VIDEO_TYPE_LIST as $video_type) {
				if (array_key_exists($video_type, $option[PARAMS_TAG_VTRACK])) {
					$service = new SvidService($option[PARAMS_TAG_VTRACK][$video_type]);
					$svid = $service->svid(false, true, true, $this->auth);
					//set vtrack ranges
					$duration = $svid[PARAMS_TAG_CAMERA][0][PARAMS_TAG_VTRACK][PARAMS_TAG_duration];
					if ($video_type === PARAMS_TAG_PLAY_FORWARD) {
						$dst_duration = $duration;
						$params_json[PARAMS_TAG_DEPLOY][PARAMS_TAG_CAMERA][0] = $svid[PARAMS_TAG_CAMERA][0];
						$params_json = $this->_set_still_tag($params_json);
					} else {
						$params_json = $this->_set_range($params_json, $video_type, $total_duration, $duration, $dst_duration);
					}
					$total_duration += $duration;

				}
			}
		}

		$params_json[PARAMS_TAG_DEPLOY][PARAMS_TAG_DURATION] = $total_duration;
		$params_json[PARAMS_TAG_DEPLOY][PARAMS_TAG_HLS] = HLS_DIR . DS . HLS_MAIN_FILE;
		return $params_json;
	}


	private function _set_still_tag($params_json): array
	{
		$time_list_path = Router::fullBaseUrl() . $this->relative_video_dir . TIME_LIST_JSON_FILE_NAME;
		$params_json[PARAMS_TAG_DEPLOY][PARAMS_TAG_CAMERA][0][PARAMS_TAG_STILL][PARAMS_TAG_TIMES] = $time_list_path;

		$still_api = ParamsJsonUtility::make_picture_url(API_PICTURE_MODE_STILL, $this->svid);
		$params_json[PARAMS_TAG_DEPLOY][PARAMS_TAG_CAMERA][0][PARAMS_TAG_STILL][PARAMS_TAG_SOURCE] = $still_api;

		$thumbnail_api = ParamsJsonUtility::make_picture_url(API_PICTURE_MODE_THUMBNAIL, $this->svid);
		$params_json[PARAMS_TAG_DEPLOY][PARAMS_TAG_CAMERA][0][PARAMS_TAG_THUMBNAIL][PARAMS_TAG_SOURCE] = $thumbnail_api;
		return $params_json;
	}

	/**
	 * @param        $params_json
	 * @param string $video_type
	 * @param int    $start
	 * @param int    $duration
	 * @param int    $dst_duration
	 * @return mixed
	 */
	private function _set_range($params_json, string $video_type, int $start, int $duration, int $dst_duration)
	{
		if ($video_type === PARAMS_TAG_PLAY_REVERSE || $video_type === PARAMS_TAG_SLOW_REVERSE) {
			$params_json[PARAMS_TAG_DEPLOY][PARAMS_TAG_CAMERA][0][PARAMS_TAG_VTRACK][$video_type][0][PARAMS_TAG_DST_RANGE] = [$dst_duration, 0];
		} else {
			$params_json[PARAMS_TAG_DEPLOY][PARAMS_TAG_CAMERA][0][PARAMS_TAG_VTRACK][$video_type][0][PARAMS_TAG_DST_RANGE] = [0, $dst_duration];
		}
		$params_json[PARAMS_TAG_DEPLOY][PARAMS_TAG_CAMERA][0][PARAMS_TAG_VTRACK][$video_type][0][PARAMS_TAG_SRC_RANGE] = [$start, $start + $duration];
		return $params_json;
	}


	/**
	 * Make bundled play list file
	 *
	 * @param string $svid
	 * @param array  $camera_options
	 * @throws FileNotFoundException
	 */
	protected function make_playlist(string $svid, array $camera_options): void
	{
		$dest_dir = SvidService::get_video_path($svid);
		$main_playlist = new File($dest_dir . HLS_DIR . DS . HLS_MAIN_FILE, true);

		$vtrack = $camera_options[0][PARAMS_TAG_VTRACK];
		$video_types = array_keys($vtrack);

		for ($i = 0; $i < count($video_types); $i++) {
			$type = $video_types[$i];
			$sub_svid = $vtrack[$type];
			$video_dir = SvidService::get_video_path($sub_svid);
			$hls_dir_path = $video_dir . HLS_DIR . DS;
			$hls_dir = new Folder($hls_dir_path);

			$bit_rate_dirs = $hls_dir->subdirectories();
			Log::info($bit_rate_dirs);
			foreach ($bit_rate_dirs as $j => $bit_rate_dir) {
				$sub_playlist = self::_copy_playlist_contents($svid, $bit_rate_dir);
				if (ArrayUtility::has_next($video_types, $i)) {
					$sub_playlist->append(HLS_EXT_X_DISCONTINUITY);
				} else {
					$sub_playlist->append(HLS_EXT_X_ENDLIST);
				}
			}

			if ($i == 0) {
				$src_main_playlist = new File($hls_dir_path . HLS_MAIN_FILE);
				$main_playlist->write($src_main_playlist->read());
			}
		}
	}


	/**
	 * @param string $svid
	 * @param string $dir
	 * @return File
	 * @throws FileNotFoundException
	 */
	private function _copy_playlist_contents(string $svid, string $dir): File
	{
		$src = HlsUtility::find_playlist($dir);
		$dest = self::_make_sub_playlist($svid, $dir);

		$fp = null;
		try {
			$fp = fopen($src, 'r');
			if ($fp) {
				while ($line = fgets($fp)) {
					$line = trim($line);
					if (StringUtility::endsWith($line, '.' . DEFAULT_SEGMENT_FILE_EXT)) {
						$path = $dir . DS . $line;
						$paths = explode(WWW_ROOT, $path, 2);
						$dest->append(Router::fullBaseUrl() . DS . $paths[1]);
					} else {
						if (StringUtility::startsWith($line, HLS_EXT_INF_HEADER)) {
							$dest->append($line);
						}
					}
				}
			}
		} finally {
			if (!empty($fp)) {
				fclose($fp);
			}
		}
		return $dest;
	}

	/**
	 * @param string $svid
	 * @param string $src_path
	 * @return File
	 * @throws FileNotFoundException
	 */
	private static function _make_sub_playlist(string $svid, string $src_path): File
	{
		$dest_dir = SvidService::get_video_path($svid);
		$dest_path = $dest_dir . HLS_DIR . DS . basename($src_path) . DS . HLS_SUB_FILE;
		$dest = new File($dest_path, true);

		if ($dest->size() == 0) {
			$dest = HlsUtility::copy_playlist_header($src_path, $dest_path);
		}

		return $dest;
	}
}
