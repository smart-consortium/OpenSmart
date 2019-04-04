<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Server $server
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $server->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $server->id)]
            )
        ?></li>
        <li><?= $this->Html->link(__('Back'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="servers form large-9 medium-8 columns content">
    <?= $this->Form->create($server) ?>
    <fieldset>
        <legend><?= __('Edit Server') ?></legend>
        <?php
        echo $this->Form->control('name');
            echo $this->Form->control('url');
        ?>
    </fieldset>
	<?= $this->Html->link(__('Cancel'),['action' => 'index'],['class'=>'button']) ?>
    <?= $this->Html->link(
			__('Delete'),
			['action' => 'delete', $server->id],
			['class'=>'button', 'confirm' => __('Are you sure you want to delete # {0}?', $server->id)]
		)
	?>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
