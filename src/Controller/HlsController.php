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

namespace App\Controller;


use App\Service\AuthTrait;
use App\Service\ParameterService;
use App\Service\SvidService;
use App\Utility\Log;
use App\Utility\StringUtility;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\Routing\Router;
use Cake\Validation\Validator;

class HlsController extends AppController
{
	use AuthTrait;

	const HTTP_QUERY_SVID = 'svid';

	const HTTP_QUERY_ID = 'id';

	const HTTP_QUERY_ACCESS_TOKEN = 'access_token';

	const HTTP_BIT_RATE = 'bit_rate';

	const APPLICATION_VND_APPLE_MPEGURL = 'application/vnd.apple.mpegurl';

	public function main()
	{
		list(, $svid, , $token) = $this->_get_queries_or_fail();
		$body = $this->_make_main_m3u8($svid, $token);
		$this->autoRender = false;
		$this->response = $this->response->withType(self::APPLICATION_VND_APPLE_MPEGURL);
		$this->response->getBody()
		               ->write($body);
	}

	/**
	 *
	 */
	public function sub()
	{
		list($id, $svid, $bit_rate, $token) = $this->_get_queries_or_fail(false);
		$video_dir = SvidService::get_video_path($svid, true);
		$hls_dir_url = Router::fullBaseUrl() . $video_dir . HLS_DIR . DS . $bit_rate . DS;
		$sub_file_url = $hls_dir_url . HLS_SUB_FILE;
		if ($this->is_auth_enabled()) {
			if (isset($token)) {
				$sub_file_url .= '?' . self::HTTP_QUERY_ACCESS_TOKEN . '=' . $token;
			}
			$sub_file_url = $this->with_auth_token($sub_file_url);
		}
		if (($lines = file($sub_file_url, FILE_IGNORE_NEW_LINES)) == false) {
			$this->autoRender = false;
			$this->response = $this->response->withType(self::APPLICATION_VND_APPLE_MPEGURL);
			$this->response->withStringBody('');
		} else {
			$body = '';
			foreach ($lines as $line) {
				if (StringUtility::startsWith($line, '#')) {
					$body .= $line . PHP_EOL;
				} else {
					$url = $line;
					if (!filter_var($line, FILTER_VALIDATE_URL)) {
						$url = $hls_dir_url . $url;
					}
					$url = $this->with_auth_token($url);
					$body .= $url . PHP_EOL;
				}

			}
			echo $body;
			$this->autoRender = false;
			$this->response = $this->response->withType(self::APPLICATION_VND_APPLE_MPEGURL);
			$this->response->withStringBody($body);
		}
	}

	private function _get_queries_or_fail(bool $is_main = true): array
	{
		$validator = new Validator();
		$validator->requirePresence(self::HTTP_QUERY_SVID)
		          ->notEmpty(self::HTTP_QUERY_SVID, __('Missing target SVID'))
		          ->url(self::HTTP_QUERY_SVID, __('Illegal SVID format'));
		if (!$is_main) {
			$validator->requirePresence(self::HTTP_QUERY_ID)
			          ->notEmpty(self::HTTP_QUERY_ID, __('Missing target ID'))
			          ->nonNegativeInteger(self::HTTP_QUERY_ID, __('Illegal ID value'))
			          ->requirePresence(self::HTTP_BIT_RATE)
			          ->notEmpty(self::HTTP_BIT_RATE, __('Missing target bit_rate'));
		}
		if (Configure::read('Auth.enabled')) {
			/*
			$validator->requirePresence(self::HTTP_QUERY_ACCESS_TOKEN)
			          ->notEmpty(self::HTTP_QUERY_ACCESS_TOKEN, __('Missing access_token'));
			*/
		}
		$errors = $validator->errors($this->request->getQueryParams());

		if (empty($errors)) {
			$id = $this->request->getQuery(self::HTTP_QUERY_ID);
			$svid = $this->request->getQuery(self::HTTP_QUERY_SVID);
			$bit_rate = $this->request->getQuery(self::HTTP_BIT_RATE);
			$token = $this->request->getQuery(self::HTTP_QUERY_ACCESS_TOKEN);
			return [$id, $svid, $bit_rate, $token];
		} else {
			$msg = '';
			foreach ($errors as $field => $error) {
				$msg .= $field . ' => ';
				foreach ($error as $index => $item) {
					$msg .= $item;
					$msg .= PHP_EOL;
				}
			}
			throw new BadRequestException($msg);
		}
	}


	private function _make_main_m3u8(string $svid, $token = ''): string
	{
		Log::debug(__('make main m3u8 data'));
		$service = new ParameterService($svid);
		$params = $service->read(true);
		$hls = $params[PARAMS_TAG_DEPLOY][PARAMS_TAG_HLS];
		$video_dir = SvidService::get_video_path($svid, true);
		$url = Router::fullBaseUrl() . $video_dir . $hls;
		$url = $this->with_auth_token($url);
		if (($lines = file($url, FILE_IGNORE_NEW_LINES)) == false) {
			return '';
		}
		$id = 1;
		$out = '';
		foreach ($lines as $line) {
			if (strpos($line, "m3u8") !== false) {
				$bit_rate = $this->__get_bit_rate($line);
				$out .= 'sub?';
				if (isset($token)) {
					$out .= self::HTTP_QUERY_ACCESS_TOKEN . '=' . $token . '&';
				}
				$out .= "svid=$svid&id=$id&bit_rate=$bit_rate" . PHP_EOL;
				++$id;
			} elseif (strpos($line, "sub?") !== false) {
				$bit_rate = $this->__get_bit_rate($line);

				$out .= 'sub?';
				if (isset($token)) {
					$out .= self::HTTP_QUERY_ACCESS_TOKEN . '=' . $token . '&';
				}
				$out .= "svid=$svid&id=$id&bit_rate=$bit_rate" . PHP_EOL;
				++$id;
			} else {
				$out .= $line . PHP_EOL;
			}
		}
		return $out;
	}

	/**
	 * @param $line
	 * @return string
	 */
	private function __get_bit_rate($line): string
	{
		$paths = mb_split(DS, $line);
		return $paths[0];
	}
}
