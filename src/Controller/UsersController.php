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
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Controller;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Http\Exception\ForbiddenException;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable  $Users
 * @property \App\Model\Table\VideosTable $Videos
 *
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class UsersController extends AppController
{

	/**
	 * Index method
	 *
	 * @return \Cake\Http\Response|void
	 */
	public function index()
	{
		$users = $this->paginate($this->Users);

		$this->set(compact('users'));
	}

	/**
	 * View method
	 *
	 * @param string|null $id User id.
	 * @return \Cake\Http\Response|void
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 */
	public function view($id = null)
	{
		$user = $this->Users->get($id, [
			'contain' => ['Videos']
		]);

		$this->set('user', $user);
//		$this->set(compact('videos'));
	}

	/**
	 * Add method
	 *
	 * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
	 */
	public function add()
	{
		$user = $this->Users->newEntity();
		if ($this->request->is('post')) {
			$user = $this->Users->patchEntity($user, $this->request->getData());
			if ($this->Users->save($user)) {
				$this->Flash->success(__('The user has been saved.'));

				return $this->redirect(['action' => 'index']);
			}
			$this->Flash->error(__('The user could not be saved. Please, try again.'));
		}
		$this->set(compact('user'));
	}

	/**
	 * Edit method
	 *
	 * @param string|null $id User id.
	 * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
	 */
	public function edit($id = null)
	{
		$user = $this->Users->get($id, [
			'contain' => []
		]);
		if ($this->request->is(['patch', 'post', 'put'])) {
			$user = $this->Users->patchEntity($user, $this->request->getData());
			if ($this->Users->save($user)) {
				$this->Flash->success(__('The user has been saved.'));

				return $this->redirect(['action' => 'index']);
			}
			$this->Flash->error(__('The user could not be saved. Please, try again.'));
		}
		$this->set(compact('user'));
	}

	/**
	 * Delete method
	 *
	 * @param string|null $id User id.
	 * @return \Cake\Http\Response|null Redirects to index.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 */
	public function delete($id = null)
	{
		$this->request->allowMethod(['post', 'delete']);
		$user = $this->Users->get($id);
		if ($this->Users->delete($user)) {
			$this->Flash->success(__('The user has been deleted.'));
		} else {
			$this->Flash->error(__('The user could not be deleted. Please, try again.'));
		}

		return $this->redirect(['action' => 'index']);
	}

	public function isLogin()
	{
		$auth_header = $this->request->getHeader('Authorized');
		if (empty($auth_header) || !is_string($auth_header)) {
			$auth_query = $this->request->getSession()
			                            ->read(SESSION_ACCESS_TOKEN);
			if (empty($auth_query) || !is_string($auth_query)) {
				return $this->response->withStatus(200);
			}
		}
		throw new ForbiddenException();
	}

	public function login()
	{
		if ($this->request->is('post')) {
			$user = $this->Auth->identify();
			if ($user) {
				$this->Auth->setUser($user);
				return $this->redirect($this->Auth->redirectUrl());
			} else {
				$this->Flash->error(__('Username or password is incorrect'));
			}
		} else {
			$auth_header = $this->request->getHeader('Authorized');
			if (empty($auth_header) || !is_string($auth_header)) {
				$auth_query = $this->request->getSession()
				                            ->read(SESSION_ACCESS_TOKEN);
				if (empty($auth_query) || !is_string($auth_query)) {
					return null;
				}
			}
			$user = $this->Auth->identify();
			if ($user) {
				$this->Auth->setUser($user);
				return $this->redirect($this->Auth->redirectUrl());
			} else {
				$this->Flash->error(__('Username or password is incorrect'));
			}
		}
	}

	public function beforeFilter(Event $event)
	{
		parent::beforeFilter($event);
		if (Configure::read('Auth.enabled')) {
			$this->Auth->allow(["add"]);
			/*
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
			*/
		}
	}

	public function logout()
	{
		if ($this->isLogin()) {
			return $this->redirect($this->Auth->logout());
		} else {
			return null;
		}
	}
}
