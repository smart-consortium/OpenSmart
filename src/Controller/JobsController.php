<?php

namespace App\Controller;
use App\Model\Entity\Job;
use App\Shell\JobManagerShell;

/**
 * Jobs Controller
 *
 * @property \App\Model\Table\JobsTable $Jobs
 *
 * @method \App\Model\Entity\Job[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class JobsController extends AppController
{

	/**
	 * Index method
	 *
	 * @return \Cake\Http\Response|void
	 */
	public function index()
	{
		$this->paginate = [
			'contain' => ['Videos']
		];
		$jobs = $this->paginate($this->Jobs, [
			'order' => [
				'Jobs.id' => 'desc'
			]
		]);

		$manager = new JobManagerShell();
		$this->set('is_alive_manager', $manager->is_alive());
		$this->set(compact('jobs'));
	}

	/**
	 * View method
	 *
	 * @param string|null $id Job id.
	 * @return \Cake\Http\Response|void
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 */
	public function view($id = null)
	{
		$job = $this->Jobs->get($id, [
			'contain' => ['Videos']
		]);

		$this->set('job', $job);
	}

	/**
	 * Delete method
	 *
	 * @param string|null $id Job id.
	 * @return \Cake\Http\Response|null Redirects to index.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 */
	public function delete($id = null)
	{
		$this->request->allowMethod(['post', 'delete']);
		$job = $this->Jobs->get($id);
		if ($this->Jobs->delete($job)) {
			$this->Flash->success(__('The job has been deleted.'));
		} else {
			$this->Flash->error(__('The job could not be deleted. Please, try again.'));
		}

		return $this->redirect(['action' => 'index']);
	}
}
