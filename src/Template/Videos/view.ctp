<?php
/**
 * @var \App\View\AppView       $this
 * @var \App\Model\Entity\Video $video
 */

use App\View\Helper\VideoViewHelper;

?>

<script type='text/javascript'>
    $(window).on('load', function () {
        var id = setInterval(function () {
            window.parent.LoadSource('<?= $video->svid ?>', true);
            clearInterval(id);
        }, 500);
    });
</script>

<div class="videos view large-12 medium-8 columns content">
    <h5><?= $this->Number->format($video->id) ?> . <?= h($video->caption) ?></h5>
	<?= $this->Html->link($video->svid, $video->svid . '?display=html') ?>
    <table class="vertical-table video_props">
        <tr>
            <th scope="row" class="video_props_header"><?= __('User') ?></th>
            <td><?= $video->has('user') ? $this->Html->link($video->user->first_name . ' ' . $video->user->family_name, ['controller' => 'Users', 'action' => 'view', $video->user->id]) : '' ?></td>
            <th scope="row" class="video_props_header"><?= __('Mode') ?></th>
            <td><?= VideoViewHelper::mode($video->mode) ?></td>
            <th scope="row" class="video_props_header"><?= __('Created') ?></th>
            <td><?= h($video->created) ?></td>
            <th scope="row" class="video_props_header"><?= __('Modified') ?></th>
            <td><?= h($video->modified) ?></td>
        </tr>
    </table>
    <div id="video_container">
		<?= $this->element('player'); ?>
    </div>

	<?php if (!empty($children)): ?>
        <div class="related">
            <h4><?= __('Related videos') ?></h4>
            <table cellpadding="0" cellspacing="0">
				<?php foreach ($children as $child): ?>
                    <tr>
                        <td>
							<?php
							$helper = new VideoViewHelper();
							$view_url = $this->Url->build(['action' => 'view', $child->id]);
							echo $helper->preview($child, 0, $video->status, $view_url);
							?>
                        </td>
                        <td class="actions">
							<?= h($child->id) ?>.
							<?= VideoViewHelper::mode($child->mode) ?>
                            <div class="caption">
								<?= $this->Html->link(h($child->caption), ['action' => 'view', $child->id]) ?>
                            </div>
                            <div>
								<?= $child->has('user') ? $this->Html->link($child->user->username, ['controller' => 'Users', 'action' => 'view', $child->user->id]) : '' ?>
								<?= h($child->modified) ?>
                            </div>
                        </td>
                    </tr>
				<?php endforeach; ?>
            </table>
        </div>
	<?php endif; ?>

    <div id="video_actions">
        <ul>
			<?php if (!empty($video->parameters)): ?>
				<?php foreach ($video->parameters as $parameters): ?>
                    <li class="link_button view_button"><?= $this->Html->link(__('View build parameter'), ['controller' => 'Parameters', 'action' => 'view', $parameters->id]) ?></li>
                    <li class="link_button edit_button"><?= $this->Html->link(__('Edit build parameter'), ['controller' => 'Parameters', 'action' => 'edit', $parameters->id]) ?></li>
				<?php endforeach; ?>
			<?php endif; ?>
            <li class="link_button rebuild_button"><?= $this->Form->postLink(__('Rebuild'), ['action' => 'rebuild', $video->id], ['confirm' => __('Are you sure you want to rebuild # {0}?', $video->id)]) ?></li>
            <li class="link_button delete_button"><?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $video->id], ['confirm' => __('Are you sure you want to delete # {0}?', $video->id)]) ?> </li>
        </ul>
    </div>
</div>
