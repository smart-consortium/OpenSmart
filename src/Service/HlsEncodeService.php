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

use App\Service\Model\MediaInfo;
use App\Utility\FileSystem\File;
use App\Utility\FileSystem\Folder;
use Cake\Console\Exception\StopException;
use Cake\Core\Configure;
use Exception;

class HlsEncodeService extends AppService
{
	use ExecuteTrait;

	const ENCODE_PROGRESS_JSON_KEY = 'progress';

	// ffmpeg command template
	const COMMAND_FORMAT = 'ffmpeg -i %s';
	const COMMAND_BIT_RATE_FORMAT = '-b:v:%d %s -b:a:%d %s';
	const COMMAND_SIZE_FORMAT = '-s:v:%d %s';
	const COMMAND_CODEC_FORMAT = "-vcodec %s -acodec %s -bsf:v h264_mp4toannexb -flags +loop-global_header -movflags faststart";
	const COMMAND_SEGMENT_FORMAT = "-f segment -segment_format mpegts -segment_time %d -segment_list %s %s";

	// array key path of HLS format
	const CONFIG_PATH_HLS = 'Build.tasks.encode.hls';

	private $ffmpeg_process_limit = 3;

	private $encode_wait_time_ms;

	public function __construct(string $svid, string $username = '', string $auth_token = '')
	{
		parent::__construct($svid, $username, $auth_token);
		$this->encode_wait_time_ms = Configure::read('System.process_wait');
		$this->ffmpeg_process_limit = Configure::read('System.ffmpeg_process_limit');
	}

	/**
	 * Service main process
	 *
	 * @param array $options Service options
	 * @return bool TRUE on success, FALSE on failure
	 * @throws Exception Database transaction error
	 */
	public function main(array $options): bool
	{
		if (!$this->_make_encode_directories($options)) {
			return CODE_FAILED;
		}

		$command = $this->_make_command($options);
		if (empty($command)) {
			return CODE_FAILED;
		}

		while (true) {
			if ($this->is_runnable()) {
				return $this->_start($command, $options);
			} else {
				sleep($this->encode_wait_time_ms);
			}
		}
	}


	public function is_runnable(): bool
	{
		list($output,) = $this->exec(FFMPEG_PROCESS_CHECK);
		if (count($output) > $this->ffmpeg_process_limit) {
			return CODE_FAILED;
		}
		return CODE_SUCCESS;
	}


	/**
	 * @param string $command
	 * @param array  $options
	 * @return bool
	 * @throws Exception
	 */
	private function _start(string $command, array $options): bool
	{
		$result = CODE_FAILED;
		try {
			$process = proc_open($command, [1 => ['pipe', 'w']], $pipes);
			if (is_resource($process)) {
				$result = $this->_monitor_process($pipes, $options);
			} else {
				$this->abort(__('Process open failed : {0}', [$command]));
			}
		} catch (StopException $e) {
			throw $e;
		} catch (Exception $e) {
			$this->abort($e->getMessage());
		} finally {
			if (isset($pipes)) {
				fclose($pipes[1]);
			}
			if (isset($process)) {
				proc_close($process);
			}
		}
		return $result;
	}

	/**
	 * Make directories for encoded files.
	 *
	 * @param array $option Encode option.
	 * @return bool Returns TRUE on success, FALSE on failure
	 */
	private function _make_encode_directories(array $option): bool
	{
		$base_dir = $option[SHELL_OPTION_OUTPUT_DIR];
		$sub_dir = $option[SHELL_OPTION_BIT_RATE_VIDEO];

		if (is_array($sub_dir)) {
			foreach ($sub_dir as $dir) {
				if (!Folder::mkdir($base_dir . DS . $dir)) {
					return CODE_FAILED;
				}
			}
			return CODE_SUCCESS;
		} else {
			return Folder::mkdir($base_dir . DS . $sub_dir);
		}
	}

	/**
	 * Make ffmpeg command
	 *
	 * @param array $option input options
	 * @return null|string ffmpeg command
	 */
	private function _make_command(array $option): string
	{
		$input_file = $option[SHELL_OPTION_INPUT_FILE];
		$output_dir = $option[SHELL_OPTION_OUTPUT_DIR];
		$bit_rate_video = $option[SHELL_OPTION_BIT_RATE_VIDEO];
		$bit_rate_audio = $option[SHELL_OPTION_BIT_RATE_AUDIO];

		$config = Configure::read(self::CONFIG_PATH_HLS);

		$command[] = sprintf(self::COMMAND_FORMAT, $input_file);
		// bit rate settings
		for ($i = 0; $i < count($bit_rate_video); $i++) {
			$command[] = sprintf(self::COMMAND_BIT_RATE_FORMAT,
			                     $i,
			                     $bit_rate_video[$i],
			                     $i,
			                     $bit_rate_audio[$i]);
		}
		// codec / segmentor settings
		for ($i = 0; $i < count($bit_rate_video); $i++) {
			$command[] = sprintf(self::COMMAND_CODEC_FORMAT,
			                     $config['video_codec'],
			                     $config['audio_codec']);

			$_dir = $output_dir . $bit_rate_video[$i] . DS;
			$segment_list = $_dir . HLS_SUB_FILE;
			$segment_file = $_dir . $config['segment_file_name'];
			$command[] = sprintf(self::COMMAND_SEGMENT_FORMAT,
			                     $config['segment_time'],
			                     $segment_list,
			                     $segment_file);
		}
		$command[] = '2>&1';

		$ret = implode(' ', $command);
		return $ret;
	}

	/**
	 * Monitoring ffmpeg process.
	 * Update parameter table on SUCCESS.
	 *
	 * @param array $pipes pipes
	 * @param array $params Encoding parameters
	 * @return bool Return true if process is SUCCESS.
	 * @throws Exception Database transaction error.
	 *                   Log file open error.
	 */
	private function _monitor_process(array $pipes, array $params): bool
	{
		$media_info = new MediaInfo($params[SHELL_OPTION_INPUT_FILE], true);
		$total_frames = $media_info->frames();

		$result = CODE_FAILED;
		$file = null;
		try {
			$file = new File(self::_log_file($params[SHELL_OPTION_OUTPUT_DIR]));

			while (!feof($pipes[1])) {
				$line = fgets($pipes[1]);
				$file->append($line);

				if ($this->__is_processed_frames_log($line)) {
					$frames = self::_get_processed_frames($line);
					self::_record_progress($frames, $total_frames, $params);
				}

				// [Ad hoc] When ffmpeg finished. Output log line contains 'overhead:'
				if ($this->__is_process_finish_log($line)) {
					$result = CODE_SUCCESS;
					// Record progress to 100%
					self::_record_progress($total_frames, $total_frames, $params);
				}
			}
			self::_update_params($params, $result);
		} catch (Exception $e) {
			throw $e;
		} finally {
			if (isset($file)) {
				$file->close();
			}
		}

		return $result;
	}


	/**
	 * Check log text is processed frames info or not
	 *
	 * @param string $line Log text
	 * @return bool TRUE on success
	 */
	private static function __is_processed_frames_log(string $line): bool
	{
		return preg_match('/frame= /', $line) == 1;
	}

	/**
	 * Check log text is finish info or not
	 *
	 * @param string $line Log text
	 * @return bool TRUE on success
	 */
	private static function __is_process_finish_log(string $line): bool
	{
		return preg_match('/overhead:/', $line) == 1;
	}

	/**
	 * Get processed frames count from ffmpeg log line.
	 *
	 * @param string $line ffmpeg output log.
	 * @return int Process frames count. Return -1 if failed to find frame info.
	 */
	private static function _get_processed_frames(string $line): int
	{
		// log line likes 'frame= xxx  fps=yyy ・・・'
		// -> 'xxx' is required
		preg_match('/frame=[\d\s]+/', $line, $matches);
		if (empty($matches)) {
			return -1;
		}

		$array = explode('=', $matches[0]);
		if (count($array) < 2) {
			return -1;
		}
		return (int)$array[1];
	}

	/**
	 * Record encoding progress
	 *
	 * @param int   $frames Processed frames.
	 * @param int   $total_frames Total frames of video.
	 * @param array $params Encoding parameters.
	 */
	private static function _record_progress(int $frames, int $total_frames, array $params): void
	{
		try {
			$file = new File(self::_progress_file($params[SHELL_OPTION_OUTPUT_DIR]), true);
			$file->lock = true;
			$contents = self::__make_progress_json($file, $frames, $total_frames, $params);
			$file->write($contents);
		} finally {
			$file->lock = false;
			$file->close();
		}
	}

	/**
	 * Make encode log file path.
	 *
	 * @param string $dir Directory path (absolute).
	 * @return string Log file path.
	 */
	private static function _log_file(string $dir): string
	{
		return $dir . DS . ENCODE_LOG_FILE_NAME;
	}

	/**
	 * Make encode progress file path.
	 *
	 * @param string $dir Directory path (absolute).
	 * @return string Encode progress file path.
	 */
	private static function _progress_file(string $dir): string
	{
		return $dir . DS . ENCODE_PROGRESS_FILE_NAME;
	}

	/**
	 * Make progress.json file contents
	 *
	 * @param File  $file CakePHP File object
	 * @param int   $frames Processed frames (numerator of progress percentage)
	 * @param int   $total_frames Total frames (denominator of progress percentage)
	 * @param array $params Task parameters
	 * @return string Progress file contents as json style string
	 */
	private static function __make_progress_json(File $file, int $frames, int $total_frames, array $params): string
	{
		$contents = $file->read();
		$percentage = self::__calc_rounded_percentage($total_frames, $frames);

		$bit_rate_video = $params[SHELL_OPTION_BIT_RATE_VIDEO];

		if (empty($contents)) {
			$json = [self::ENCODE_PROGRESS_JSON_KEY => []];
		} else {
			$json = json_decode($contents, true);
		}

		if (is_array($bit_rate_video)) {
			foreach ($bit_rate_video as $rate) {
				$json[self::ENCODE_PROGRESS_JSON_KEY][$rate] = $percentage;
			}
		} else {
			$json[self::ENCODE_PROGRESS_JSON_KEY][$bit_rate_video] = $percentage;
		}

		return json_encode($json, JSON_PRETTY_PRINT);
	}

	/**
	 * Calc percentage
	 * @param int $denominator denominator
	 * @param int $numerator numerator
	 * @return int rounded percentage
	 */
	private static function __calc_rounded_percentage(int $denominator, int $numerator): int
	{
		return (int)round($numerator / $denominator * 100);
	}

	/**
	 * Update encoding status in parameter table.
	 *
	 * @param array $shell_option Shell options
	 * @param bool  $status TRUE on success encoding
	 * @throws Exception Database transaction error
	 */
	private static function _update_params(array $shell_option, bool $status)
	{
		$service = new ParameterService($shell_option[SHELL_OPTION_SVID]);

		$service->update(function ($body) use ($shell_option, $status) {
			$target = basename($shell_option[SHELL_OPTION_INPUT_FILE]);
			$bit_rate_video = $shell_option[SHELL_OPTION_BIT_RATE_VIDEO];
			$bit_rate_audio = $shell_option[SHELL_OPTION_BIT_RATE_AUDIO];

			if (is_array($bit_rate_video)) {
				for ($i = 0; $i < count($bit_rate_video); $i++) {
					$body = self::__update_params($body, $target, $bit_rate_video[$i], $bit_rate_audio[$i], $status);
				}
			} else {
				$body = self::__update_params($body, $target, $bit_rate_video, $bit_rate_audio, $status);
			}
			return $body;
		});
	}


	private static function __update_params($body, $target, $bit_rate_video, $bit_rate_audio, $status)
	{
		if (array_key_exists(PARAMS_TAG_TARGET, $body[PARAMS_TAG_ENCODE])) {
			$body[PARAMS_TAG_ENCODE][PARAMS_TAG_BIT_RATES][] = [PARAMS_TAG_BIT_RATE_VIDEO => $bit_rate_video,
			                                                    PARAMS_TAG_BIT_RATE_AUDIO => $bit_rate_audio,
			                                                    PARAMS_TAG_STATUS         => $status];
		} else {
			for ($i = 0; $i < count($body[PARAMS_TAG_ENCODE]); $i++) {
				if ($body[PARAMS_TAG_ENCODE][$i][PARAMS_TAG_TARGET] === $target) {
					$body[PARAMS_TAG_ENCODE][$i][PARAMS_TAG_BIT_RATES][] = [PARAMS_TAG_BIT_RATE_VIDEO => $bit_rate_video,
					                                                        PARAMS_TAG_BIT_RATE_AUDIO => $bit_rate_audio,
					                                                        PARAMS_TAG_STATUS         => $status];
				}
			}
		}
		return $body;
	}
}