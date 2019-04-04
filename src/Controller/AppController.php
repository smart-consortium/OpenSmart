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

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\Event;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/3.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{

	const AUTH_FIELD_KEY_NAME = 'username';

	const AUTH_FIELD_KEY_PASSWORD = 'password';

	/**
	 * accessed user name
	 *
	 * @var string
	 */
	protected $username = '';

	/**
	 * Initialization hook method.
	 *
	 * Use this method to add common initialization code like loading components.
	 *
	 * e.g. `$this->loadComponent('Security');`
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function initialize()
	{
		parent::initialize();

		$this->loadComponent('RequestHandler',
			['enableBeforeRedirect' => false]
		);
		$this->loadComponent('Flash');

		/*
		 * Enable the following components for recommended CakePHP security settings.
		 * see https://book.cakephp.org/3.0/en/controllers/components/security.html
		 */
		$this->loadComponent('Security');
		$this->loadComponent('Csrf');

		/* Authentication options */
		if (Configure::read('Auth.enabled')) {
			$this->loadComponent('Auth', [
				'authenticate' => [
					'Form' => [
						'fields' => [
							'username' => self::AUTH_FIELD_KEY_NAME,
							'password' => self::AUTH_FIELD_KEY_PASSWORD
						]
					],
				],
				'loginAction'  => [
					'controller' => 'Users',
					'action'     => 'login'
				]
			]);

			$this->username = $this->Auth->user(self::AUTH_FIELD_KEY_NAME);

			/*
			$this->loadComponent('Auth', [
				'authenticate' => [
					'OAuth2Token',
					'OAuth2Form' => [
						'fields' => [
							'username' => 'username',
							'password' => 'password'
						]
					],
				],
				'loginAction' => [
					'controller' => 'Users',
					'action' => 'login'
				]
			]);
			*/
		} else {
			$this->username = Configure::read('Auth.default_user_name');
		}
	}
/*
	public function beforeFilter(Event $event)
	{
		if (($token = $this->request->getQuery('access_token')) != null) {
			$this->request->getSession()->write(SESSION_ACCESS_TOKEN, $token);
		} elseif ($token =  $this->request->getHeader('Authorized')) {
			$this->request->getSession()->write(SESSION_ACCESS_TOKEN, $token);
		}

		$authUser = $this->Auth->user();
		// Here you are setting AccessToken, RefresToken and ExpiresIn to all the controllers and views in order to handle later
		// you can set cookies or whatever you need
		$this->set('authUser', $authUser);
		$this->set('access_token', $authUser['access_token']);
		$this->set('refresh_token', $authUser['refresh_token']);
		$this->set('token_expires', $authUser['expires_in']);

		return parent::beforeFilter($event);
	}
*/
}
