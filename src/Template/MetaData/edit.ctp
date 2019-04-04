<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\MetaData $metaData
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $metaData->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $metaData->id)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Meta Data'), ['action' => 'index']) ?></li>
        <li><?= $this->Html->link(__('List Videos'), ['controller' => 'Videos', 'action' => 'index']) ?></li>
        <li><?= $this->Html->link(__('New Video'), ['controller' => 'Videos', 'action' => 'add']) ?></li>
    </ul>
</nav>
<div class="metaData form large-9 medium-8 columns content">
    <?= $this->Form->create($metaData) ?>
    <fieldset>
        <legend><?= __('Edit Meta Data') ?></legend>
        <?php
            echo $this->Form->control('video_id', ['options' => $videos]);
            echo $this->Form->control('data');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
