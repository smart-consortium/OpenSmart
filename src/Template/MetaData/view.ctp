<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\MetaData $metaData
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Meta Data'), ['action' => 'edit', $metaData->id]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Meta Data'), ['action' => 'delete', $metaData->id], ['confirm' => __('Are you sure you want to delete # {0}?', $metaData->id)]) ?> </li>
        <li><?= $this->Html->link(__('List Meta Data'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Meta Data'), ['action' => 'add']) ?> </li>
        <li><?= $this->Html->link(__('List Videos'), ['controller' => 'Videos', 'action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Video'), ['controller' => 'Videos', 'action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="metaData view large-9 medium-8 columns content">
    <h3><?= h($metaData->id) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Video') ?></th>
            <td><?= $metaData->has('video') ? $this->Html->link($metaData->video->id, ['controller' => 'Videos', 'action' => 'view', $metaData->video->id]) : '' ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Data') ?></th>
            <td><?= h($metaData->data) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id') ?></th>
            <td><?= $this->Number->format($metaData->id) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Created') ?></th>
            <td><?= h($metaData->created) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Modified') ?></th>
            <td><?= h($metaData->modified) ?></td>
        </tr>
    </table>
</div>
