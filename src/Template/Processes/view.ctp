<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Process $process
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Process'), ['action' => 'edit', $process->id]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Process'), ['action' => 'delete', $process->id], ['confirm' => __('Are you sure you want to delete # {0}?', $process->id)]) ?> </li>
        <li><?= $this->Html->link(__('List Processes'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Process'), ['action' => 'add']) ?> </li>
        <li><?= $this->Html->link(__('List Jobs'), ['controller' => 'Jobs', 'action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Job'), ['controller' => 'Jobs', 'action' => 'add']) ?> </li>
        <li><?= $this->Html->link(__('List Processes'), ['controller' => 'Processes', 'action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Process'), ['controller' => 'Processes', 'action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="processes view large-9 medium-8 columns content">
    <h3><?= h($process->id) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Id') ?></th>
            <td><?= $this->Number->format($process->id) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Job Id') ?></th>
            <td><?= $this->Number->format($process->job_id) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Process Id') ?></th>
            <td><?= $this->Number->format($process->process_id) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Child Process') ?></th>
            <td><?= $this->Number->format($process->child_process) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Created') ?></th>
            <td><?= h($process->created) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Modified') ?></th>
            <td><?= h($process->modified) ?></td>
        </tr>
    </table>
    <div class="related">
        <h4><?= __('Related Jobs') ?></h4>
        <?php if (!empty($process->jobs)): ?>
        <table cellpadding="0" cellspacing="0">
            <tr>
                <th scope="col"><?= __('Id') ?></th>
                <th scope="col"><?= __('Process Id') ?></th>
                <th scope="col"><?= __('Video Id') ?></th>
                <th scope="col"><?= __('Name') ?></th>
                <th scope="col"><?= __('Command') ?></th>
                <th scope="col"><?= __('Status') ?></th>
                <th scope="col"><?= __('Log') ?></th>
                <th scope="col"><?= __('Start') ?></th>
                <th scope="col"><?= __('End') ?></th>
                <th scope="col"><?= __('Created') ?></th>
                <th scope="col"><?= __('Modified') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
            <?php foreach ($process->jobs as $jobs): ?>
            <tr>
                <td><?= h($jobs->id) ?></td>
                <td><?= h($jobs->process_id) ?></td>
                <td><?= h($jobs->video_id) ?></td>
                <td><?= h($jobs->name) ?></td>
                <td><?= h($jobs->command) ?></td>
                <td><?= h($jobs->status) ?></td>
                <td><?= h($jobs->log) ?></td>
                <td><?= h($jobs->start) ?></td>
                <td><?= h($jobs->end) ?></td>
                <td><?= h($jobs->created) ?></td>
                <td><?= h($jobs->modified) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['controller' => 'Jobs', 'action' => 'view', $jobs->id]) ?>
                    <?= $this->Html->link(__('Edit'), ['controller' => 'Jobs', 'action' => 'edit', $jobs->id]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['controller' => 'Jobs', 'action' => 'delete', $jobs->id], ['confirm' => __('Are you sure you want to delete # {0}?', $jobs->id)]) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
    <div class="related">
        <h4><?= __('Related Processes') ?></h4>
        <?php if (!empty($process->processes)): ?>
        <table cellpadding="0" cellspacing="0">
            <tr>
                <th scope="col"><?= __('Id') ?></th>
                <th scope="col"><?= __('Job Id') ?></th>
                <th scope="col"><?= __('Process Id') ?></th>
                <th scope="col"><?= __('Child Process') ?></th>
                <th scope="col"><?= __('Created') ?></th>
                <th scope="col"><?= __('Modified') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
            <?php foreach ($process->processes as $processes): ?>
            <tr>
                <td><?= h($processes->id) ?></td>
                <td><?= h($processes->job_id) ?></td>
                <td><?= h($processes->process_id) ?></td>
                <td><?= h($processes->child_process) ?></td>
                <td><?= h($processes->created) ?></td>
                <td><?= h($processes->modified) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['controller' => 'Processes', 'action' => 'view', $processes->id]) ?>
                    <?= $this->Html->link(__('Edit'), ['controller' => 'Processes', 'action' => 'edit', $processes->id]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['controller' => 'Processes', 'action' => 'delete', $processes->id], ['confirm' => __('Are you sure you want to delete # {0}?', $processes->id)]) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
</div>
