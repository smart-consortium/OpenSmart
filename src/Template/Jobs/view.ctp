<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Job $job
 */
?>
<div class="jobs view large-9 medium-8 columns content">
    <h3><?= h($job->name) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Video') ?></th>
            <td><?= $job->has('video') ? $this->Html->link($job->video->id, ['controller' => 'Videos', 'action' => 'view', $job->video->id]) : '' ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Name') ?></th>
            <td><?= h($job->name) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Log') ?></th>
            <td><?= h($job->log) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id') ?></th>
            <td><?= $this->Number->format($job->id) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Status') ?></th>
            <td><?= $this->Number->format($job->status) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Start') ?></th>
            <td><?= h($job->start) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('End') ?></th>
            <td><?= h($job->end) ?></td>
        </tr>
    </table>
    <div class="row">
        <h4><?= __('Command') ?></h4>
        <?= $this->Text->autoParagraph(h($job->command)); ?>
    </div>

</div>
