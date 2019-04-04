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
use App\Utility\Log;
use Exception;

/**
 * Class BundleService
 * @package App\Service
 */
abstract class BundleService extends AppService
{
	/**
	 * Service main process
	 *
	 * @param array $options Service options
	 * @return bool TRUE on success, FALSE on failure
	 * @throws Exception
	 */
	public function main(array $options)
	{
		if ($this->can_bundle($options[PARAMS_TAG_CAMERA])) {
			$this->make_preview($options[PARAMS_TAG_CAMERA]);
			$this->make_time_list($options[PARAMS_TAG_CAMERA]);
			$this->update_params_json($options[PARAMS_TAG_CAMERA]);
			$this->make_playlist($this->svid, $options[PARAMS_TAG_CAMERA]);
			$this->update_status(true);
			return CODE_SUCCESS;
		} else {
			return CODE_FAILED;
		}
	}

	protected function can_bundle(array $camera_options): bool
	{
		Log::debug($camera_options);
		return CODE_SUCCESS;
	}

	protected function make_preview(array $camera_options): bool
	{
		Log::debug($camera_options);
		return CODE_SUCCESS;
	}

	protected function make_time_list(array $camera_options): bool
	{
		Log::debug($camera_options);
		return CODE_SUCCESS;
	}

	/**
	 * Read and update params.json
	 *
	 * @param array $camera_options
	 * @return bool TRUE on success, FALSE on failure.
	 * @throws Exception
	 */
	private function update_params_json(array $camera_options): bool
	{
		$service = new ParameterService($this->svid);
		$params_json = $service->read();
		$params_json = $this->update($params_json, $camera_options);

		$service->update(function () use ($params_json) {
			return $params_json;
		});
		return CODE_SUCCESS;
	}

	/**
	 * Update params.json
	 *
	 * @param mixed $params_json
	 * @param array $camera_options
	 * @return mixed
	 */
	abstract protected function update($params_json, array $camera_options);

	/**
	 * Make bundled play list file
	 *
	 * @param string $svid
	 * @param array  $camera_options
	 * @throws FileNotFoundException
	 */
	abstract protected function make_playlist(string $svid, array $camera_options): void;

	/**
	 * Update video status
	 *
	 * @param bool $status Deploy status
	 * @return bool TRUE on success, FALSE on failure.
	 * @throws Exception
	 */
	protected function update_status(bool $status): bool
	{
		$service = new ParameterService($this->svid);
		$params_json = $service->read();
		$service->update(function () use ($params_json, $status) {
			$params_json[PARAMS_TAG_DEPLOY][PARAMS_TAG_STATUS] = $status;
			return $params_json;
		});
		$service->dump();
		return CODE_SUCCESS;
	}
}
