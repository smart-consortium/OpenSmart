<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * MetaData Controller
 *
 * @property \App\Model\Table\MetaDataTable $MetaData
 *
 * @method \App\Model\Entity\MetaData[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class MetaDataController extends AppController
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
        $metaData = $this->paginate($this->MetaData);

        $this->set(compact('metaData'));
    }

    /**
     * View method
     *
     * @param string|null $id Meta Data id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $metaData = $this->MetaData->get($id, [
            'contain' => ['Videos']
        ]);

        $this->set('metaData', $metaData);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $metaData = $this->MetaData->newEntity();
        if ($this->request->is('post')) {
            $metaData = $this->MetaData->patchEntity($metaData, $this->request->getData());
            if ($this->MetaData->save($metaData)) {
                $this->Flash->success(__('The meta data has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The meta data could not be saved. Please, try again.'));
        }
        $videos = $this->MetaData->Videos->find('list', ['limit' => 200]);
        $this->set(compact('metaData', 'videos'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Meta Data id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $metaData = $this->MetaData->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $metaData = $this->MetaData->patchEntity($metaData, $this->request->getData());
            if ($this->MetaData->save($metaData)) {
                $this->Flash->success(__('The meta data has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The meta data could not be saved. Please, try again.'));
        }
        $videos = $this->MetaData->Videos->find('list', ['limit' => 200]);
        $this->set(compact('metaData', 'videos'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Meta Data id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $metaData = $this->MetaData->get($id);
        if ($this->MetaData->delete($metaData)) {
            $this->Flash->success(__('The meta data has been deleted.'));
        } else {
            $this->Flash->error(__('The meta data could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
