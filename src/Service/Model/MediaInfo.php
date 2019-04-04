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

namespace App\Service\Model;

use App\Utility\FileSystem\File;

/**
 * Class MediaInfo
 *
 * @package App\Service\Model
 */
class MediaInfo
{
	const DELIMITER_FRAME_RATE = '/';

	/**
	 * ffprobe command
	 *
	 * @var string
	 */
	const COMMAND = 'ffprobe -hide_banner -i %s -loglevel quiet -show_streams -print_format json 2>/dev/null';

	/**
	 * ffprobe command output
	 * codec type video
	 *
	 * @var string
	 */
	const CODEC_TYPE_VIDEO = 'video';

	/**
	 * ffprobe command output
	 * codec type audio
	 *
	 * @var string
	 */
	const CODEC_TYPE_AUDIO = 'audio';

	/**
	 * ffprobe command output
	 * average frame rate
	 *
	 * @var string
	 */
	const ITEM_AVG_FRAME_RATE = 'avg_frame_rate';

	/**
	 * ffprobe command output
	 * movie total duration
	 *
	 * @var string
	 */
	const ITEM_DURATION = 'duration';

	/**
	 * ffprobe command output
	 * num of frames
	 *
	 * @var string
	 */
	const ITEM_NB_FRAMES = 'nb_frames';

	/**
	 * ffprobe command output
	 * streams
	 *
	 * @var string
	 */
	const ITEM_STREAMS = 'streams';

	/**
	 * ffprobe command output
	 * codec type
	 *
	 * @var string
	 */
	const ITEM_CODEC_TYPE = 'codec_type';

	/**
	 * ffprobe command output
	 * video width
	 *
	 * @var string
	 */
	const ITEM_WIDTH = 'width';

	/**
	 * ffprobe command output
	 * video height
	 *
	 * @var string
	 */
	const ITEM_HEIGHT = 'height';


	/**
	 * Media file path
	 *
	 * @var string
	 */
	private $media_path = '';

	/**
	 * ffprobe output
	 *
	 * @var mixed
	 */
	private $info = null;

	/**
	 * video stream json
	 *
	 * @var mixed
	 */
	private $video_stream = null;

	/**
	 * Audio stream json
	 *
	 * @var mixed
	 */
	private $audio_stream = null;

	/**
	 * MediaInfo constructor.
	 *
	 * @param string $media_path Media file path
	 * @param bool   $use_cache Use cache file preferentially
	 * @param bool   $update_cache
	 */
	public function __construct(string $media_path, bool $use_cache = false, bool $update_cache = false)
	{
		$this->media_path = $media_path;
		if ($use_cache) {
			$file = new File($media_path);
			$folder = $file->folder();
			$cache_file = new File($folder->path . DS . MEDIA_INFO_FILE_NAME);
			if ($cache_file->exists() && !$update_cache) {
				$this->info = $cache_file->read_as_json();
			} else {
				$this->info = $this->_read($media_path);
				$cache_file->write_as_json($this->info);
			}
		} else {
			$this->info = $this->_read($media_path);
		}

		$this->video_stream = $this->_video_stream();
		$this->audio_stream = $this->_audio_stream();
	}

	/**
	 * TRUE if media has video streams.
	 * @return bool
	 */
	public function has_video_stream(): bool
	{
		return $this->video_stream != null;
	}

	/**
	 * TRUE if media has audio streams.
	 * @return bool
	 */
	public function has_audio_stream(): bool
	{
		return $this->audio_stream != null;
	}

	public function width(): int
	{
		if ($this->__has_video_info(self::ITEM_WIDTH)) {
			return $this->video_stream[self::ITEM_WIDTH];
		}
		return -1;
	}

	public function height(): int
	{
		if ($this->__has_video_info(self::ITEM_HEIGHT)) {
			return $this->video_stream[self::ITEM_HEIGHT];
		}
		return -1;
	}

	public function frames(): int
	{
		if ($this->__has_video_info(self::ITEM_NB_FRAMES)) {
			return (int)$this->video_stream[self::ITEM_NB_FRAMES];
		}
		return -1;
	}

	public function duration(): int
	{
		if ($this->__has_video_info(self::ITEM_DURATION)) {
			return ceil($this->video_stream[self::ITEM_DURATION] * 1000);
		}
		return -1;
	}

	public function average_frame_rate(): int
	{
		if ($this->__has_video_info(self::ITEM_AVG_FRAME_RATE)) {
			$str = $this->video_stream[self::ITEM_AVG_FRAME_RATE];
			list($den, $num) = explode(self::DELIMITER_FRAME_RATE, $str, 2);
			return round($den / $num);
		}
		return -1;
	}

	/**
	 * @param string $media_path
	 * @return array
	 */
	private function _read(string $media_path): array
	{
		$process = new Process(self::COMMAND, [$media_path]);
		list(, $output) = $process->start(false, false);

		if (is_array($output)) {
			$json_string = implode(PHP_EOL, $output);
		} else {
			$json_string = $output;
		}
		return json_decode($json_string, true);
	}

	/**
	 * Read video stream json
	 *
	 * @return array
	 */
	private function _video_stream(): array
	{
		return $this->__get_stream(self::CODEC_TYPE_VIDEO);
	}

	/**
	 * Read audio stream json
	 *
	 * @return array
	 */
	private function _audio_stream(): array
	{
		return $this->__get_stream(self::CODEC_TYPE_AUDIO);
	}

	private function __get_stream(string $type): array
	{
		if (!isset($this->info)) {
			return [];
		}

		if (array_key_exists(self::ITEM_STREAMS, $this->info)) {
			foreach ($this->info[self::ITEM_STREAMS] as $stream) {
				if (array_key_exists(self::ITEM_CODEC_TYPE, $stream)) {
					if ($stream[self::ITEM_CODEC_TYPE] === $type) {
						return $stream;
					}
				}
			}
		}
		return [];
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	private function __has_video_info(string $key): bool
	{
		return isset($this->video_stream) && array_key_exists($key, $this->video_stream);
	}
}