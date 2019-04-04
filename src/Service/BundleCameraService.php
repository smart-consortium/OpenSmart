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
use App\Utility\StringUtility;
use Cake\Routing\Router;

/**
 * Class BundleCameraService
 * Bundle multiplex camera(contains single or multiplex video) to single video
 *
 * @package App\Service
 */
class BundleCameraService extends BundleService
{
	public function can_bundle(array $camera_options): bool
	{
		$duration = -1;
		foreach ($camera_options as $index => $camera) {
			$svid = $camera[PARAMS_TAG_SVID];
			$service = new SvidService($svid);
			$svid_json = $service->svid(false,true,false);
			if ($duration === -1) {
				$duration = $svid_json[SVID_TAG_VIDEO][SVID_TAG_duration];
			} else {
				if ($duration !== $svid_json[SVID_TAG_VIDEO][SVID_TAG_duration]) {
					Log::info('Different durations');
					return CODE_FAILED;
				}
			}
		}
		Log::debug($camera_options);
		return CODE_SUCCESS;
	}

	public function make_preview(array $camera_options): bool
	{
		foreach ($camera_options as $index => $camera) {
			$svid = $camera[PARAMS_TAG_SVID];
			$service = new ImageService($svid);
			$service->copy_preview_image($this->preview_dir, $index);
		}
		Log::debug($camera_options);
		return CODE_SUCCESS;
	}

	/**
	 * Make bundled play list file
	 *
	 * @param string $svid
	 * @param array  $camera_options
	 */
	public function make_playlist(string $svid, array $camera_options): void
	{
		$dest_dir = SvidService::get_video_path($svid);
		$main_playlist = new File($dest_dir . HLS_DIR . DS . HLS_MAIN_FILE, true);

		foreach ($camera_options as $index => $camera_opt) {
			$video_dir = SvidService::get_video_path($camera_opt[PARAMS_TAG_SVID]);
			$hls_dir_path = $video_dir . HLS_DIR . DS;
			$hls_dir = new Folder($hls_dir_path);

			$bit_rate_dirs = $hls_dir->subdirectories();

			foreach ($bit_rate_dirs as $j => $bit_rate_dir) {
				$sub_playlist = $this->_copy_playlist_contents($svid, $bit_rate_dir);

				if (ArrayUtility::has_next($camera_options, $index)) {
					$sub_playlist->append(HLS_EXT_X_DISCONTINUITY);
				} else {
					$sub_playlist->append(HLS_EXT_X_ENDLIST);
				}
			}

			if ($index == 0) {
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
					if (filter_var($line, FILTER_VALIDATE_URL)) {
						$dest->append($line);
					} elseif (StringUtility::endsWith($line, '.' . DEFAULT_SEGMENT_FILE_EXT)) {
						$path = $dir . DS . $line;
						$paths = explode(WWW_ROOT, $path, 2);
						$dest->append(Router::fullBaseUrl() . DS . $paths[1]);
					} else {
						if (StringUtility::startsWith($line, HLS_EXT_INF_HEADER)) {
							$dest->append($line);
						} else {
							if (StringUtility::startsWith($line, HLS_EXT_X_DISCONTINUITY)) {
								$dest->append($line);
							}
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

	protected function update($params_json, array $camera_options): array
	{
		$total_duration = 0;

		foreach ($camera_options as $index => $camera_opt) {
			$service = new SvidService($camera_opt[SHELL_OPTION_SVID]);
			$svid = $service->svid(false, true, true, false);
			$params_json[PARAMS_TAG_DEPLOY][PARAMS_TAG_CAMERA][] = $svid[PARAMS_TAG_CAMERA][0];
			$params_json[PARAMS_TAG_DEPLOY][PARAMS_TAG_CAMERA][$index][PARAMS_TAG_NAME] = $camera_opt[PARAMS_TAG_NAME];

			list($params_json, $total_duration) = $this->_shift_src_range($params_json, $index);
			// copy bit rate info
			if ($index == 0) {
				$params_json = $this->_copy_video_options($camera_opt, $params_json);
			}
		}

		$params_json[PARAMS_TAG_DEPLOY][PARAMS_TAG_DURATION] = $total_duration;
		$params_json[PARAMS_TAG_DEPLOY][PARAMS_TAG_HLS] = HLS_DIR . DS . HLS_MAIN_FILE;
		return $params_json;
	}

	/**
	 * @param     $params_json
	 * @param int $index
	 * @return array params.json and end of time range.
	 */
	private function _shift_src_range($params_json, int $index): array
	{
		$vtrack = $params_json[PARAMS_TAG_DEPLOY][PARAMS_TAG_CAMERA][$index][PARAMS_TAG_VTRACK];
		if ($index > 0) {
			$prev_vtrack = $params_json[PARAMS_TAG_DEPLOY][PARAMS_TAG_CAMERA][$index - 1][PARAMS_TAG_VTRACK];
			$shift = $this->_last_time_of_vtrack($prev_vtrack);
		} else {
			$shift = 0;
		}

		$last = 0;
		foreach ($vtrack as $tag => $item) {
			if ($tag === PARAMS_TAG_duration) {
				continue;
			}
			$range = [$item[0][PARAMS_TAG_SRC_RANGE][0] + $shift,
			          $item[0][PARAMS_TAG_SRC_RANGE][1] + $shift];

			$params_json[PARAMS_TAG_DEPLOY][PARAMS_TAG_CAMERA][$index][PARAMS_TAG_VTRACK][$tag][0][PARAMS_TAG_SRC_RANGE] = $range;
			$last = $range[1];
		}
		return [$params_json, $last];
	}

	/**
	 * @param $camera
	 * @param $params_json
	 * @return mixed
	 */
	private function _copy_video_options($camera, $params_json)
	{
		$service = new ParameterService($camera[SHELL_OPTION_SVID]);
		$source_params_json = $service->read(true);
		foreach ($source_params_json[PARAMS_TAG_BUILD] as $task) {
			if ($task[PARAMS_TAG_COMMAND] === SHELL_COMMAND_NAME_ENCODE) {
				foreach ($task[PARAMS_TAG_OPTIONS][PARAMS_TAG_BIT_RATES] as $rate) {
					$params_json[PARAMS_TAG_VIDEOS][PARAMS_TAG_BIT_RATES][] = $rate[PARAMS_TAG_BIT_RATE_VIDEO];
				}
			}
		}
		return $params_json;
	}

	/**
	 * Get shift time
	 * Shift time = Last end time of video type (play_forward , slow_forward .. etc) in camera
	 * @param array $vtrack
	 * @return int
	 */
	private function _last_time_of_vtrack(array $vtrack): int
	{
		$shift = 0;
		foreach (PARAMS_TAG_VIDEO_TYPE_LIST as $type) {
			if (array_key_exists($type, $vtrack)) {
				$end = $vtrack[$type][0][PARAMS_TAG_SRC_RANGE][1];
				if ($end > $shift) {
					$shift = $end;
				}
			}
		}
		return $shift;
	}
}
