<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Process[]|\Cake\Collection\CollectionInterface $processes
 */
?>
<div class="processes index large-9 medium-8 columns content">
    <h3><?= __('Processes') ?></h3>
    <table cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('id') ?></th>
                <th scope="col"><?= $this->Paginator->sort('job_id') ?></th>
                <th scope="col"><?= $this->Paginator->sort('process_id') ?></th>
                <th scope="col"><?= $this->Paginator->sort('child_process') ?></th>
                <th scope="col"><?= $this->Paginator->sort('created') ?></th>
                <th scope="col"><?= $this->Paginator->sort('modified') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($processes as $process): ?>
            <tr>
                <td><?= $this->Number->format($process->id) ?></td>
                <td><?= $this->Number->format($process->job_id) ?></td>
                <td><?= $this->Number->format($process->process_id) ?></td>
                <td><?= $this->Number->format($process->child_process) ?></td>
                <td><?= h($process->created) ?></td>
                <td><?= h($process->modified) ?></td>
                <td class="actions">
                    <?= $this->Form->postLink(__('Stop'), ['action' => 'stop', $process->id], ['confirm' => __('Are you sure you want to stop process # {0}?', $process->id)]) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(['format' => __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')]) ?></p>
    </div>
</div>
