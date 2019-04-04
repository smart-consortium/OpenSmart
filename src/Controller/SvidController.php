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


use App\Service\ParameterService;
use App\Service\SvidService;
use App\Utility\Log;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Routing\Router;

class SvidController extends AppController
{
	const QUERY_DISPLAY = 'display';

	const DISPLAY_MODE_HTML = 'html';

	public function initialize()
	{
		parent::initialize();
		$this->loadComponent("RequestHandler");
	}

	public function index()
	{
	}

	public function view($user, $year, $date, $time, $cam = null)
	{
		$token = '';
		if (Configure::read('Auth.enabled')) {
			$token = $this->request->getSession()
			                       ->read(SESSION_ACCESS_TOKEN);
		}

		$svid = Router::url(null, true);
		$service = new ParameterService($svid);
		$params = $service->read();

		if ($this->_is_ready($params)) {
			$ss = new SvidService($svid);
			$this->set('status', true);
			$this->set('svid', $ss->svid(false, true, false));
		} else {
			$this->set('status', false);
		}
	}

	public function video($user, $year, $date, $time, $cam = null)
	{
		$token = '';
		if (Configure::read('Auth.enabled')) {
			$token = $this->request->getSession()
			                       ->read(SESSION_ACCESS_TOKEN);
		}
		$display = $this->request->getQuery(self::QUERY_DISPLAY);

		$svid = Router::url(null, true);
		$svid = strtok($svid,'?');
		$service = new ParameterService($svid);
		$params = $service->read();
		$body = $this->_get_svid_body($params, $svid);
		if ($display === self::DISPLAY_MODE_HTML) {
			$this->set('svid', $body);
		} else {
			$this->autoRender = false;
			$this->response = $this->response->withType("application/json")
			                                 ->withStringBody($body);
		}
	}

	public function beforeFilter(Event $event)
	{
		parent::beforeFilter($event);

		if (Configure::read('Auth.enabled')) {
			$this->Auth->allow(["video"]);
		}
	}

	/**
	 * @param array  $params
	 * @param string $svid
	 * @return array|null|string
	 */
	private function _get_svid_body(array $params, string $svid)
	{
		if ($this->_is_ready($params)) {
			$service = new SvidService($svid);
			return $service->svid(true, true, false);
		} else {
			return __('{"status":false,"message" :"Streaming is not ready. Now processing."}');
		}
	}

	/**
	 * @param $params
	 * @return bool
	 */
	private function _is_ready($params): bool
	{
		if (empty($params)) {
			return false;
		}
		if (array_key_exists(PARAMS_TAG_DEPLOY, $params)) {
			if (array_key_exists(PARAMS_TAG_STATUS, $params[PARAMS_TAG_DEPLOY])) {
				return $params[PARAMS_TAG_DEPLOY][PARAMS_TAG_STATUS] == true;
			}
		}
		return false;
	}
}