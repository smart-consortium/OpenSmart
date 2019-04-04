<?php
/**
 * OpenSmart :
 * Copyright (c) Smart Consortium. (https://smart-consortium.org)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Smart Consortium. (https://smart-consortium.org)
 * @link          https://smart-consortium.org OpenSmart Project
 * @since         0.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Service;

use App\Exception\FileNotFoundException;
use App\Exception\InvalidArgumentException;
use App\Model\Entity\Parameter;
use App\Service\Model\MediaInfo;
use App\Utility\FileSystem\File;
use App\Utility\Log;
use App\Utility\ParamsJsonUtility;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Exception\RolledbackTransactionException;

/**
 * Class ParameterService
 *
 * Handle video parameters (parameter.json or parameters table)
 * @package App\Service
 * @property \App\Model\Table\VideosTable     $Videos
 * @property \App\Model\Table\ParametersTable $Parameters
 *
 */
class ParameterService extends AppService
{
	const CAMERA_NAME_TEMPLATE = 'Camera #%03d';

	/**
	 * Read params.json data
	 *
	 * @param bool $use_cache True : use cache
	 * @return array|null params.json array. NULL returns if svid is not found on table.
	 * @throws FileNotFoundException
	 */
	public function read(bool $use_cache = false)
	{
		if ($use_cache) {
			$file = new File($this->video_dir . PARAMS_JSON_FILE_NAME);
			if ($file->exists()) {
				return $file->read_as_json();
			}
			throw new FileNotFoundException(__('params.json cache file is not found.'));
		} else {
			$service = new SvidService($this->svid);

			$entity = $this->Parameters->find()
			                           ->select([Parameter::ID, Parameter::BODY])
			                           ->where([Parameter::VIDEO_ID => $service->get_video_id()])
			                           ->first();
			return empty($entity) ? null : $entity->get(Parameter::BODY);
		}
	}

	/**
	 * Add new record to parameters table
	 *
	 * @param array $shell_options Preset options.
	 * @return bool TRUE on success, FALSE on failure.
	 * @throws RecordNotFoundException
	 */
	public function add(array $shell_options = []): bool
	{
		$camera_mode = $this->__get_camera_mode($shell_options);

		Log::info('log_msg_make_parameter_json', $this->svid);
		$template = ParamsJsonUtility::template($camera_mode);

		if (empty($template)) {
			Log::error('log_msg_load_params_failed');
			throw new InvalidArgumentException(__('Load parameter.json template failed'));
		}

		$template = $this->_preset($template, $shell_options);
		return $this->_add($template) ? CODE_SUCCESS : CODE_FAILED;
	}

	/**
	 * @param array $json_data
	 * @return int
	 * @throws RecordNotFoundException
	 */
	private function _add(array $json_data): int
	{
		$entity = $this->Parameters->newEntity();
		$service = new SvidService($this->svid);
		$entity->video_id = $service->get_video_id();
		$entity->body = $json_data;
		return $this->Parameters->save($entity) ? (int)$entity->id : -1;
	}

	/**
	 * Dump params.json file
	 *
	 * @param array|null $params params.json data
	 */
	public function dump(array $params = null)
	{
		$file = new File($this->video_dir . PARAMS_JSON_FILE_NAME, true);
		if (empty($params)) {
			$params = $this->read();
		}
		$file->write_as_json($params);
	}

	/**
	 * @param callable $callback
	 * @throws \Cake\ORM\Exception\RolledbackTransactionException;
	 * @throws \Exception
	 */
	public function update(callable $callback)
	{
		$connection = $this->Parameters->getConnection();
		$svid = $this->svid;
		try {
			$connection->transactional(function () use ($svid, $callback) {
				$query = $this->Videos->find_by_svid($svid);

				$entity = $this->Parameters->find()
				                           ->select()
				                           ->where(['video_id' => $query->id])
				                           ->first();
				$body = $entity->get('body');
				$body = $callback($body);
				$entity->set('body', $body);
				$this->Parameters->save($entity);
			});
		} catch (RolledbackTransactionException $e) {
			$connection->rollback();
			throw $e;
		} catch (\Exception $e) {
			$connection->rollback();
			throw $e;
		}
	}

	/**
	 * Set template to preset options
	 * @param array $template params.json template data array
	 * @param array $shell_options Preset data of shell options
	 * @return array
	 */
	private function _preset(array $template, array $shell_options): array
	{
		$template[PARAMS_TAG_CAPTION] = $this->__get_caption($shell_options);
		switch ($this->__get_camera_mode($shell_options)) {
			case CAMERA_MODE_MULTI:
				$template = $this->__preset_target_camera_svid($template, $shell_options);
				break;
			case CAMERA_MODE_TRICK:
				$template = $this->__preset_target_video_svid($template, $shell_options);
				break;
			default:
				$template = $this->__preset_target_videos($template, $shell_options);
				break;
		}
		if (array_key_exists(SHELL_OPTION_TARGETS, $shell_options)) {
			foreach ($shell_options[SHELL_OPTION_TARGETS] as $item) {
				$info = new MediaInfo($this->video_dir . $item, true);
				$width = $info->width();
				$height = $info->height();

			}
		}
		return $template;
	}

	/**
	 * @param array $template
	 * @param array $shell_options
	 * @return array
	 */
	private function __preset_target_camera_svid(array $template, array $shell_options): array
	{
		foreach ($shell_options[SHELL_OPTION_TARGETS] as $index => $target) {
			$template[PARAMS_TAG_BUILD][0][PARAMS_TAG_OPTIONS][PARAMS_TAG_CAMERA][]
				= [PARAMS_TAG_NAME => $this->__make_camera_name($index),
				   PARAMS_TAG_SVID => $target];
		}
		return $template;
	}

	/**
	 * @param array $template
	 * @param array $shell_options
	 * @return array
	 */
	private function __preset_target_video_svid(array $template, array $shell_options): array
	{
		$camera[PARAMS_TAG_NAME] = 'Camera #' . sprintf('%03d', 1);
		$camera[PARAMS_TAG_SVID] = $this->svid;

		foreach (SHELL_OPTION_TARGET_TYPE_LIST as $type) {
			if (array_key_exists($type, $shell_options)) {
				$video_type = ParamsJsonUtility::to_camelcase_video_type($type);
				$camera[PARAMS_TAG_VTRACK][$video_type] = $shell_options[$type];
			}
		}
		$template[PARAMS_TAG_BUILD][0][PARAMS_TAG_OPTIONS][PARAMS_TAG_CAMERA][] = $camera;
		return $template;
	}

	/**
	 * Set encode target videos to params.json template
	 *
	 * @param array $template
	 * @param array $shell_options
	 * @return array
	 */
	private function __preset_target_videos(array $template, array $shell_options): array
	{
		$targets = $shell_options[SHELL_OPTION_TARGETS];
		$template[PARAMS_TAG_VIDEOS] = empty($targets) ? [] : $targets;

		if (array_key_exists(PARAMS_TAG_BUILD, $template)) {
			for ($i = 0; $i < count($template[PARAMS_TAG_BUILD]); $i++) {
				//
				if ($template[PARAMS_TAG_BUILD][$i][PARAMS_TAG_COMMAND] === SHELL_COMMAND_NAME_ENCODE) {
					$template[PARAMS_TAG_BUILD][$i][PARAMS_TAG_OPTIONS] += [PARAMS_TAG_VIDEOS => $template[PARAMS_TAG_VIDEOS]];
				}
			}
		}
		return $template;
	}

	/**
	 * @param array $shell_options
	 * @return int|mixed
	 */
	private function __get_camera_mode(array $shell_options)
	{
		if (array_key_exists(SHELL_OPTION_CAMERA_MODE, $shell_options)) {
			return $shell_options[SHELL_OPTION_CAMERA_MODE];
		}
		return CAMERA_MODE_SINGLE;
	}

	/**
	 * @param array $shell_options
	 * @return mixed|string
	 */
	private function __get_caption(array $shell_options)
	{
		if (array_key_exists(PARAMS_TAG_CAPTION, $shell_options)) {
			return $shell_options[PARAMS_TAG_CAPTION];
		}
		return $this->svid;
	}

	/**
	 * @param $index
	 * @return string
	 */
	private function __make_camera_name($index): string
	{
		return sprintf(self::CAMERA_NAME_TEMPLATE, $index + 1);
	}
}
