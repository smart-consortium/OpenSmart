<?php

namespace App\Controller;

use App\Utility\Log;

/**
 * Parameters Controller
 *
 * @property \App\Model\Table\ParametersTable $Parameters
 *
 * @method \App\Model\Entity\Parameter[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ParametersController extends AppController
{

	/**
	 * View method
	 *
	 * @param string|null $id Parameter id.
	 * @return \Cake\Http\Response|void
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 */
	public function view($id = null)
	{
		$parameter = $this->Parameters->get($id, [
			'contain' => ['Videos']
		]);
		$parameter->body = self::json_encode($parameter->body);
		$this->set(compact('parameter'));
	}

	/**
	 * Edit method
	 *
	 * @param string|null $id Parameter id.
	 * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
	 */
	public function edit($id = null)
	{
		$parameter = $this->Parameters->get($id, [
			'contain' => []
		]);
		if ($this->request->is(['patch', 'post', 'put'])) {
			$body = $this->request->getData('body');
			$parameter->body = json_decode($body,true);
			if ($this->Parameters->save($parameter)) {
				$this->Flash->success(__('The parameter has been saved.'));
				return $this->redirect(['action' => 'view', $id]);
			}
			$this->Flash->error(__('The parameter could not be saved. Please, try again.'));
		}
		$parameter->body = self::json_encode($parameter->body);
		$this->set(compact('parameter'));
	}

	private static function json_encode($body)
	{
		return json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}
}
