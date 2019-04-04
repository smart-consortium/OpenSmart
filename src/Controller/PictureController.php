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

namespace App\Controller;

use App\Service\ImageService;
use Cake\Http\Exception\NotFoundException;
use InvalidArgumentException;

class PictureController extends AppController
{
	public function initialize()
	{
		parent::initialize();
		$this->loadComponent("RequestHandler");
	}

	/**
	 * @throws \App\Exception\FileNotFoundException
	 */
	public function index()
	{
		$svid = $this->request->getQuery('svid');
		$mode = $this->request->getQuery('mode');
		$ms_str = $this->request->getQuery('ms');
		try {
			list($ms, $ext) = self::_parse_ms($ms_str);

			$service = new ImageService($svid);
			$path = $service->make($ms, $ext, $mode);
			if (empty($path)) {
				throw new NotFoundException('Image not found or can\'t create ' . $ms);
			} else {
				if (filter_var($path, FILTER_VALIDATE_URL)) {
					$this->autoRender = false;
					$this->response = $this->redirect($path, 301);
				} else {
					$this->autoRender = false;
					$this->response = $this->response->withHeader('Content-Type', 'image/jpeg');
					$this->response = $this->response->withHeader('Access-Control-Allow-Origin', '*');
					$this->response = $this->response->withFile($path);
				}
			}
		} catch (InvalidArgumentException $ex) {
			throw new NotFoundException('Image not found or can\'t create');
		}
	}

	private static function _parse_ms($in)
	{
		$ms = -1;
		$ext = DEFAULT_IMG_EXT;

		$n = sscanf($in, '/%d.%s', $ms, $ext);
		if ($ms < 0) {
			throw new InvalidArgumentException($in);
		}
		if ($n == 2) {
			return [$ms, $ext];
		}
		throw new InvalidArgumentException($in);
	}
}
