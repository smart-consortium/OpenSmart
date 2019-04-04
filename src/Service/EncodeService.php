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

use App\Service\Model\CakeProcess;
use App\Utility\HlsUtility;
use App\Utility\Log;
use Cake\Core\Configure;
use Exception;

/**
 * Class EncodeService
 * @package App\Service
 */
class EncodeService extends AppService
{
	private $process_time_limit;

	private $process_wait_time_ms;

	public function __construct(string $svid, string $username = '', string $auth_token = '')
	{
		parent::__construct($svid, $username, $auth_token);
		$this->process_time_limit = Configure::read('System.process_time_limit');
		$this->process_wait_time_ms = Configure::read('System.process_wait');

	}

	/**
	 * Service main process
	 *
	 * @param array $videos Encode target videos
	 * @param array $options Service options
	 * @return bool TRUE on success, FALSE on failure
	 * @throws Exception Database transaction error
	 */
	public function main(array $videos, array $options): bool
	{
		$encode_type = $this->_get_encode_type($options);
		$video_rates = [];
		$audio_rates = [];

		foreach ($options['bit_rates'] as $rates) {
			$video_rates[] = $rates['video'];
			$audio_rates[] = $rates['audio'];
		}

		$video_dir = $this->_get_output_dir($encode_type);

		// execute encoding each target video
		foreach ($videos as $video) {
			// update parameter table for target video encoding status
			$this->_update_params_encode_output($this->svid, $encode_type, $video);

			if (!$this->_encode($encode_type, $video, $video_rates, $audio_rates, $video_dir)) {
				return CODE_FAILED;
			}
		}

		// Wait all sub encode task finish.
		for ($i = 0; $i < $this->process_time_limit; ++$i) {
			if ($this->_check_process_finished($videos, $options[PARAMS_TAG_BIT_RATES])) {
				break;
			}
			sleep($this->process_wait_time_ms);
		}

		// Make adaptive bit rate streaming meta file
		if ($this->_is_adaptive_bit_rate_streaming($options)) {
			HlsUtility::make_manifest($video_dir, $options[PARAMS_TAG_BIT_RATES]);
		}
		return CODE_SUCCESS;
	}


	/**
	 * Update encode status in parameters table
	 *
	 * @param string $svid SVID
	 * @param string $encode_type Encode type
	 * @param string $target Encode target video name
	 * @throws Exception Database transaction error
	 */
	private static function _update_params_encode_output(string $svid, string $encode_type, string $target): void
	{
		$service = new ParameterService($svid);
		$service->update(function ($body) use ($encode_type, $target) {
			$body[PARAMS_TAG_ENCODE][] = [PARAMS_TAG_TYPE      => $encode_type,
			                              PARAMS_TAG_TARGET    => $target,
			                              PARAMS_TAG_BIT_RATES => []];
			return $body;
		});
	}

	/**
	 * Execute encode
	 *
	 * @param string $encode_type Encode type
	 * @param string $target Encode target video name
	 * @param array  $video_rates Encode bit rates of video
	 * @param array  $audio_rates Encode bit rates of audio
	 * @param string $video_dir Output directory
	 * @return bool TRUE on success, FALSE on failure
	 */
	private function _encode(string $encode_type, string $target, array $video_rates, array $audio_rates, string $video_dir): bool
	{
		$args = ['svid'           => $this->svid,
		         'encode_type'    => $encode_type,
		         'input_file'     => $this->video_dir . $target,
		         'bit_rate_video' => $video_rates,
		         'bit_rate_audio' => $audio_rates,
		         'output_dir'     => $video_dir];
		$process = new CakeProcess('build encode hls', $args);
		list($return_var, $output) = $process->start(Configure::read('Build.tasks.encode.background'));
		Log::debug($output);
		return $return_var == 0;
	}

	/**
	 * Get video directory path from encode type
	 *
	 * @param string $encode_type Encode type (only 'HLS' is accept now)
	 * @return string Video directory path
	 */
	private function _get_output_dir(string $encode_type): string
	{
		$video_dir = $this->video_dir;
		switch ($encode_type) {
			case ENCODE_TYPE_HLS:
				$video_dir .= HLS_DIR;
				break;
			default:
				$video_dir .= HLS_DIR;
				break;
		}
		return $video_dir . DS;
	}

	/**
	 * Check encode sub process
	 *
	 * eg)
	 *   build_out: {
	 *      encode : [
	 *          {
	 *              target : 'video1',
	 *              bit_rates : [{ video: xx, audio: yy }, {・・}],
	 *              status : bool
	 *          },
	 *          {
	 *              target : 'video2',
	 *              bit_rates : [{・・}, {・・}],
	 *              status : bool
	 *          }
	 *      ]
	 * @param array $target_names
	 * @param array $bit_rate_in
	 * @return bool
	 * @throws Exception
	 */
	private function _check_process_finished(array $target_names, array $bit_rate_in)
	{
		$service = new ParameterService($this->svid);
		$body = $service->read();

		if (!array_key_exists(PARAMS_TAG_ENCODE, $body)) {
			return false;
		}

		$encode_out = $body[PARAMS_TAG_ENCODE];
		if (empty($encode_out)) {
			return false;
		}

		if (count($encode_out) == 1) {
			// single video file
			return $this->__check_target_process_finished($encode_out[0], $bit_rate_in);
		} else {
			// multiple video files
			foreach ($target_names as $name) {
				$out = array_search($name, array_column($encode_out, PARAMS_TAG_TARGET));
				$result = $this->__check_target_process_finished($encode_out[$out], $bit_rate_in);
				if (!$result) {
					return false;
				}
			}
			return true;
		}
	}

	/**
	 * Check process status
	 *
	 * @param array $encode_out Encoding output parameter
	 * @param array $bit_rate_in Encoding bit rates
	 * @return bool Return TRUE on all process finished
	 * @throws Exception Some error occurred in sub task
	 */
	private function __check_target_process_finished(array $encode_out, array $bit_rate_in): bool
	{
		if (!array_key_exists(PARAMS_TAG_BIT_RATES, $encode_out)) {
			return false;
		}

		if (count($encode_out[PARAMS_TAG_BIT_RATES]) !== count($bit_rate_in)) {
			return false;
		}

		foreach ($encode_out[PARAMS_TAG_BIT_RATES] as $rate) {
			if (!array_key_exists(PARAMS_TAG_STATUS, $rate)) {
				return false;
			}
			if (!$rate[PARAMS_TAG_STATUS]) {
				throw new Exception('ffmpeg task failed.');
			}
		}
		return true;
	}

	/**
	 * Return encoding type
	 *
	 * @param array $options Options to call this instance
	 * @return string Encode type
	 */
	private function _get_encode_type(array $options): string
	{
		switch ($options['type']) {
			case ENCODE_TYPE_HLS:
				return ENCODE_TYPE_HLS;
			default:
				return ENCODE_TYPE_HLS;
		}
	}

	/**
	 * Use adaptive bit rate streaming or not.
	 *
	 * @param array $options Encode options
	 * @return bool TRUE on parameter has multiple bit rates.
	 */
	private function _is_adaptive_bit_rate_streaming(array $options): bool
	{
		return count($options[PARAMS_TAG_BIT_RATES]) >= 2;
	}


}
