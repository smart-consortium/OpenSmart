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


use App\Exception\InvalidArgumentException;

class ParamsJson
{

	public $version = 9.5;

	private $params = [];

	/**
	 * ParamsJson constructor.
	 * @param array $params
	 * @throws InvalidArgumentException
	 */
	public function __construct(array $params)
	{
		if (empty($params)) {
			throw new InvalidArgumentException(__('Video parameter is empty or not found'));
		}
		$this->params = $params;
	}

	public static function template($version = 9.5)
	{

	}

	public function get_version(): float
	{
		return $this->version;
	}

	public function get_caption(): string
	{
		return $this->params[PARAMS_TAG_CAPTION];
	}

	public function set_caption(string $caption)
	{
		$this->params[PARAMS_TAG_CAPTION] = $caption;
	}

	public function get_deploy(): array
	{
		if (array_key_exists(PARAMS_TAG_DEPLOY, $this->params)) {
			return $this->params[PARAMS_TAG_DEPLOY];
		}
		return [];
	}

	public function get_cameras(): array
	{
		$deploy = $this->get_deploy();
		if (!empty($deploy)) {
			return $deploy[PARAMS_TAG_CAMERA];
		}
		return [];
	}

	public function get_camera(int $camera_num): array
	{
		$cameras = $this->get_cameras();
		if ($camera_num < 0 || count($cameras) <= $camera_num) {
			throw new InvalidArgumentException(__('Invalid camera number'));
		}
		return $cameras[$camera_num];
	}

	public function get_thumbnail(int $camera_num):array
	{
		$camera = $this->get_camera($camera_num);
		if (array_key_exists(PARAMS_TAG_THUMBNAIL, $camera)) {
			return $camera[PARAMS_TAG_THUMBNAIL];
		}
		return [];
	}
	public function get_thumbnail_size(int $camera_num):array
	{
		$thumbnail = $this->get_thumbnail($camera_num);
		if (array_key_exists(PARAMS_TAG_SIZE, $thumbnail)) {
			return $thumbnail[PARAMS_TAG_SIZE];
		}
		return [];
	}

	public function get_deploy_status(): bool
	{
		$deploy = $this->get_deploy();
		if (!empty($deploy)) {
			if (array_key_exists(PARAMS_TAG_STATUS, $deploy)) {
				return $deploy[PARAMS_TAG_STATUS] == true;
			}
		}
		return false;
	}


	public function get(string $path)
	{
		$tags = mb_split('.', $path);
		$item = $this->params;
		foreach ($tags as $tag) {
			$item = $item[$tag];
		}
		return $item;
	}
}