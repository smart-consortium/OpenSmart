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


use App\Exception\InvalidArgumentException;
use App\Exception\InvalidMediaFormatException;
use App\Service\Model\MediaInfo;
use App\Service\Model\Process;
use App\Utility\FileSystem\File;
use Cake\Core\Configure;
use Exception;

class PreBuildService extends AppService
{
	private $width_ratio;

	private $height_ratio;

	public function __construct(string $svid, string $username = '', string $auth_token = '')
	{
		parent::__construct($svid, $username, $auth_token);
		$this->width_ratio = Configure::read('System.video_width_ratio');
		$this->height_ratio = Configure::read('System.video_height_ratio');
	}

	/**
	 * @param array $option
	 * @return array
	 * @throws Exception
	 */
	public function main(array $option): array
	{

		if ($this->_is_rebuild($option)) {
			$service = new CleanUpService($this->svid);
			$service->main();
		}
		switch ($option[SHELL_OPTION_CAMERA_MODE]) {
			case CAMERA_MODE_SINGLE:
				foreach ($option[SHELL_OPTION_TARGETS] as $item) {
					$info = new MediaInfo($this->video_dir . $item, true);
					if (!$info->has_video_stream()) {
						throw new InvalidMediaFormatException(__('Illegal file type. Video stream not found.'));
					}
					$overwrite = true;
					if ($this->_is_need_padding($info)) {
						$this->_padding($item, $info, $overwrite);
						new MediaInfo($this->video_dir . $item, true, true);
						$overwrite = false;
					}
					if ($this->_is_need_transcode($option)) {
						$this->_transcode($item, $overwrite);
					}
				}
				return $option[SHELL_OPTION_TARGETS];
			case CAMERA_MODE_TRICK:
				return [];
			case CAMERA_MODE_MULTI:
				return $option[SHELL_OPTION_TARGETS];
			default:
				throw new InvalidArgumentException(__('Invalid camera mode option.'));
		}
	}

	/**
	 * @param array $option
	 * @return mixed
	 */
	private function _is_rebuild(array $option)
	{
		return array_key_exists(SHELL_OPTION_REBUILD, $option);
	}

	/**
	 * @param array $option
	 * @return bool
	 */
	private function _is_need_transcode(array $option): bool
	{
		return array_key_exists(SHELL_OPTION_NORMALIZE, $option);
	}

	/**
	 * @param string $target
	 * @param bool   $overwrite
	 * @return string
	 * @throws Exception
	 */
	private function _transcode(string $target, bool $overwrite = true): string
	{
		$source = new File($this->video_dir . $target);
		$input = $this->_move_to_original($source, $overwrite);
		$output = new File($this->video_dir . $source->name() . '.' . DEFAULT_VIDEO_EXT);

		$process = new Process(FFMPEG_TRANSCODE, [$input->path, $output->path]);
		$process->start(false, true);

		return basename($output->path);
	}

	/**
	 * @param MediaInfo $media_info
	 * @return bool
	 */
	private function _is_need_padding(MediaInfo $media_info): bool
	{
		return $media_info->width() < $media_info->height();
	}

	/**
	 * @param string    $target
	 * @param MediaInfo $media_info
	 * @param bool      $overwrite
	 * @return string
	 * @throws Exception
	 */
	private function _padding(string $target, MediaInfo $media_info, bool $overwrite = true): string
	{
		$source = new File($this->video_dir . $target);
		$input = $this->_move_to_original($source, $overwrite);
		$output = new File($this->video_dir . $source->name() . '.' . DEFAULT_VIDEO_EXT);
		$width = $media_info->width();
		$unit = $media_info->height() / $this->height_ratio;
		$_width = $unit * $this->width_ratio;
		$_pad = ($_width - $width) / 2;
		$process = new Process(FFMPEG_VIDEO_PADDING, [$input->path, $_width, $_pad, $output->path]);
		$process->start(false, true);

		return basename($output->path);
	}

	/**
	 * @param File $source
	 * @param bool $overwrite
	 * @return File|null
	 * @throws Exception
	 */
	private function _move_to_original(File $source, bool $overwrite = true)
	{
		$path = $this->video_dir . ORIGINALS_DIR . DS . basename($source->path);
		$dest = new File($path, true);
		if ($dest->exists()) {
			if ($overwrite) {
				$moved = $source->move($dest->path, $overwrite);
				if (empty($moved)) {
					$this->abort('Move original file failed:' . $source->path);
				} else {
					return new File($moved);
				}
			} else {
				return $source;
			}
		} else {
			$moved = $source->move($dest->path, $overwrite);
			if (empty($moved)) {
				$this->abort('Move original file failed:' . $source->path);
			} else {
				return new File($moved);
			}
		}
	}
}