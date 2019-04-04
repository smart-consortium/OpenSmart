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

namespace App\Service;

use App\Utility\FileSystem\Folder;
use App\Utility\Log;
use App\Utility\Server;
use Exception;

/**
 * Class StorageService
 *
 * @package App\Service
 * @property \App\Model\Table\ServersTable $Servers
 * @property \App\Model\Table\UsersTable   $Users
 * @property \App\Model\Table\VideosTable  $Videos
 *
 */
class StorageService extends AppService
{

	/**
	 * Process upload video via HTTP POST
	 *
	 * @param int    $index camera index
	 * @param string $video_type post video type
	 * @return array Video ID and Uploaded target paths.
	 * @throws Exception
	 */
	public function post(int $index = 0, string $video_type = ''): array
	{
		Log::debug('POST new video : ' . $this->svid);
		$target = $this->_put($index, $video_type);

		if (empty($target)) {
			$this->abort('POST video failed.');
		}
		$id = $this->__add_record();
		return [$id, [$target]];
	}

	/**
	 * Process create multi/trick video
	 *
	 * @param int   $camera_mode trick or multi camera mode
	 * @param array $children_svid
	 * @param array $options
	 * @return array base directory path and video id
	 * @throws Exception
	 */
	public function create(int $camera_mode, array $children_svid, array $options = []): array
	{
		Log::info('log_msg_create_video', $this->svid);
		$base = $this->__make_video_dirs(true);

		if (empty($base)) {
			$this->abort('msg_create_video_failed');
		}
		$id = $this->Videos->add($camera_mode, $this->username, $this->svid, $children_svid, $options);
		if ($id < 0) {
			$this->abort('Add video to video table failed.');
		}
		Log::info('Add new video => [svid = ' . $this->svid . ']');
		return [$id, $base];
	}

	/**
	 * @param int    $index
	 * @param string $video_type
	 * @return string
	 * @throws Exception
	 */
	private function _put(int $index, string $video_type): string
	{
		$target = $this->__put($index, $video_type);
		return $target;
	}


	/**
	 * Move uploaded file to video directory
	 *
	 * @param int    $index Target file index
	 * @param string $video_type Target video type
	 * @return string Target file name
	 * @throws Exception Move file failed.
	 */
	private function __put(int $index, string $video_type): string
	{
		$video_dir = $this->__make_video_dirs();
		if (empty($video_dir)) {
			$this->abort('log_msg_directory_create_failed', $this->svid);
		}

		$tmp_name = $_FILES[$video_type]['tmp_name'][$index];
		$name = basename($_FILES[$video_type]['name'][$index]);

		if (!move_uploaded_file($tmp_name, $video_dir . $name)) {
			$this->abort('log_msg_post_move_file_failed');
		}
		return $name;
	}

	private function __send(int $index, string $video_type, string $url): string
	{
		$tmp_name = $_FILES[$video_type]['tmp_name'][$index];
		$name = basename($_FILES[$video_type]['name'][$index]);
		//TODO rename
		$curl = curl_init();
		//$file = fopen($tmp_name, "r+");
		curl_setopt_array($curl, [
			CURLOPT_URL         => $url,
			CURLOPT_POST        => true,
			CURLOPT_POSTFIELDS  => [
				'play_forward[0]' => new \CURLFile($tmp_name),
			],
		]);
		/*
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_UPLOAD, 1);
		curl_setopt($curl, CURLOPT_INFILE, $file);
		*/
		curl_exec($curl);
		curl_close($curl);
		//fclose($file);
		return $name;
	}

	/**
	 * Make video directory with children.
	 *
	 * @param bool $is_bundle
	 * @return string Camera directory
	 */
	private function __make_video_dirs(bool $is_bundle = false): string
	{
		$base = SvidService::get_video_path($this->svid);
		Folder::mkdir($base);
		Folder::mkdir($base . HLS_DIR);
		Folder::mkdir($base . PREVIEW_DIR);
		if (!$is_bundle) {
			Folder::mkdir($base . THUMBNAIL_DIR);
			Folder::mkdir($base . STILL_DIR);
		}
		return $base;
	}

	/**
	 * @return int
	 * @throws Exception
	 */
	private function __add_record(): int
	{
		$options = ['caption' => h($_POST['caption'])];
		$id = $this->Videos->add(CAMERA_MODE_SINGLE, $this->username, $this->svid, [], $options);
		if ($id < 0) {
			$this->abort('Add video to video table failed => [svid = ' . $this->svid . ']');
		}
		Log::info('Add new video => [svid = ' . $this->svid . ']');
		return $id;
	}
}
