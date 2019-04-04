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


use App\Controller\VideosController;
use App\Utility\FileSystem\File;
use App\Utility\FileSystem\Folder;
use Cake\ORM\TableRegistry;

/**
 * Class CleanUpService
 *
 * This class development tool
 *
 * @package App\Service
 * @property \App\Model\Table\VideosTable $Videos

 */
class CleanUpService extends AppService
{
	/**
	 * Service main
	 *
	 * @throws \Exception
	 */
	public function main()
	{
		if (!$this->_clean()) {
			$this->abort(__('Clean up error.'));
		}
		return CODE_SUCCESS;
	}

	private function _clean(): bool
	{
		$video_id = $this->_find_by_svid();
		if ($video_id < 0) {
			return false;
		}
		if ($this->_unset_parameters($video_id)) {
			return $this->_remove_contents();
		} else {
			return false;
		}
	}

	/**
	 * @return int
	 */
	private function _find_by_svid(): int
	{
		$element = $this->Videos->find_by_svid($this->svid);
		if (!empty($element)) {
			return $element->id;
		}
		return -1;
	}

	/**
	 * @param $video_id
	 * @return bool
	 */
	private function _unset_parameters($video_id): bool
	{
		$table = TableRegistry::get(TABLE_NAME_PARAMETERS);
		$element = $table->find()
		                 ->where(['video_id' => $video_id])
		                 ->first();
		$body = $element->get('body');
		unset($body[PARAMS_TAG_ENCODE]);
		unset($body[PARAMS_TAG_DEPLOY]);
		$element->set('body', $body);
		if ($table->save($element)) {
			return true;
		}
		return false;
	}


	/**
	 * @return bool
	 */
	private function _remove_contents(): bool
	{
		$dir = new Folder($this->video_dir);
		foreach ($dir->subdirectories() as $sub) {
			$sub_dir = new Folder($sub);
			if ($sub_dir->delete()) {
				Folder::mkdir($sub_dir->path);
			}
		}
		$media_info = new File($dir->path . MEDIA_INFO_FILE_NAME);
		if (!$media_info->delete()) {
			return false;
		}

		$time_list = new File($dir->path . TIME_LIST_JSON_FILE_NAME);
		if (!$time_list->delete()) {
			return false;
		}

		$params = new File($dir->path . PARAMS_JSON_FILE_NAME);
		if (!$params->delete()) {
			return false;
		}
		return true;
	}
}