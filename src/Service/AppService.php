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

use App\Exception\InvalidArgumentException;
use App\Utility\Log;
use Cake\Core\Configure;
use Cake\Datasource\ModelAwareTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use Exception;

/**
 * Class AppService
 * Base class of  Services
 * @package App\Service
 */
class AppService
{
	/**
	 *
	 */
	use LocatorAwareTrait;

	/**
	 *
	 */
	use ModelAwareTrait;

	/**
	 * The name of the service in camelized.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * The SVID URL of target video.
	 *
	 * @var string
	 */
	public $svid = null;

	/**
	 * Plugins
	 *
	 * @var null
	 */
	public $plugin = null;


	/**
	 * Base video directory.
	 *
	 * @var string
	 */
	protected $video_dir;

	/**
	 * Relative video directory path
	 * ex) videos/user/y/m/d/
	 * @var
	 */
	protected $relative_video_dir;

	/**
	 * HLS encoded file directory
	 * @var string
	 */
	protected $hls_dir;

	/**
	 * Thumbnail file directory
	 * @var string
	 */
	protected $thumbnail_dir;

	/**
	 * Still file directory
	 * @var string
	 */
	protected $still_dir;

	/**
	 * Preview file directory
	 * @var string
	 */
	protected $preview_dir;

	/**
	 * Auth component is enabled or not.
	 *
	 * @var bool
	 */
	protected $auth;

	/**
	 * User name
	 *
	 * @var string
	 */
	protected $username;

	/**
	 * @var string Authentication token string
	 */
	protected $auth_token;

	protected $storage_server;
	protected $encoding_server;
	protected $web_server;

	/**
	 * AppService constructor.
	 * @param string $svid
	 * @param string $username
	 * @param string $auth_token
	 */
	public function __construct(string $svid, string $username = '', string $auth_token = '')
	{
		list(, $name) = namespaceSplit(get_class($this));
		$this->name = substr($name, 0, -10);

		if (!$this->name) {
			list(, $class) = namespaceSplit(get_class($this));
			$this->name = str_replace('Service', '', $class);
		}
		$this->_initialize_server();
		$this->initialize($svid, $username, $auth_token);
	}

	public function initialize(string $svid, string $username = '', string $auth_token = '')
	{
		$this->auth = Configure::read('Auth.enabled');
		if ($this->auth) {
			$this->auth_token = $auth_token;
		}
		$this->username = $username;
		$this->svid = $svid;
		if (empty($this->svid)) {
			Log::error(__('SVID is not specified'));
			throw new InvalidArgumentException(__('SVID is not specified'));
		}
		$this->_initialize_directories();
	}

	/**
	 * Initialize video directories (For storage server)
	 */
	private function _initialize_directories()
	{
		$this->video_dir = SvidService::get_video_path($this->svid);
		$this->relative_video_dir = SvidService::get_video_path($this->svid, true);
		$this->hls_dir = $this->video_dir . HLS_DIR . DS;
		$this->still_dir = $this->video_dir . STILL_DIR . DS;
		$this->thumbnail_dir = $this->video_dir . THUMBNAIL_DIR . DS;
		$this->preview_dir = $this->video_dir . PREVIEW_DIR . DS;
	}

	/**
	 * Magic accessor for model auto loading.
	 *
	 * @param string $name Property name
	 * @return bool|object The model instance or false
	 */
	public function __get($name)
	{
		return $this->loadModel($name);
	}

	/**
	 * Abort service
	 * @param string $singular Text to translate.
	 * @param array  ...$args Array with arguments or multiple arguments in function.
	 * @throws Exception
	 */
	public function abort($singular, ...$args)
	{
		Log::error(__($singular, $args));
		throw new Exception(__($singular, $args));
	}

	private function _initialize_server(): void
	{
		$this->encoding_server = Configure::read('System.encoding_server');
		$this->storage_server = Configure::read('System.storage_server');
		$this->web_server = Configure::read('System.web_server');
	}
}
