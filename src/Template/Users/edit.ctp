<?php
/**
 * @var \App\View\AppView      $this
 * @var \App\Model\Entity\User $user
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
				__('Delete'),
				['action' => 'delete', $user->id],
				['confirm' => __('Are you sure you want to delete # {0}?', $user->id)]
			)
			?></li>
        <li><?= $this->Html->link(__('Back'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="users form large-9 medium-8 columns content">
	<?= $this->Form->create($user) ?>
    <fieldset>
        <legend><?= __('Edit User') ?></legend>
		<?php
            echo $this->Form->control('username', ['disabled' => true]);
		    echo $this->Form->control('display_name');
            echo $this->Form->control('first_name');
            echo $this->Form->control('family_name');
            echo $this->Form->control('email');
            echo $this->Form->control('password');
		?>
    </fieldset>
	<?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'button']) ?>
	<?= $this->Html->link(__('Delete'), ['action' => 'index'], ['class' => 'button']) ?>
	<?= $this->Form->button(__('Submit')) ?>
	<?= $this->Form->end() ?>
</div>
