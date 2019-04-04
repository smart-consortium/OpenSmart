<?php
/**
 * @var \App\View\AppView      $this
 * @var \App\Model\Entity\User $user
 */

use App\View\Helper\VideoViewHelper;

?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit'), ['action' => 'edit', $user->id]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $user->id], ['confirm' => __('Are you sure you want to delete # {0}?', $user->id)]) ?> </li>
    </ul>
</nav>
<div class="users view large-9 medium-8 columns content">
    <h3><?= h($user->id) ?> . <?= h($user->display_name) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('ID') ?></th>
            <td><?= $this->Number->format($user->id) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Name') ?></th>
            <td><?= h($user->username) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Display Name') ?></th>
            <td><?= h($user->display_name) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('First Name') ?></th>
            <td><?= h($user->first_name) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Family Name') ?></th>
            <td><?= h($user->family_name) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Email') ?></th>
            <td><?= h($user->email) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Created') ?></th>
            <td><?= h($user->created) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Modified') ?></th>
            <td><?= h($user->modified) ?></td>
        </tr>
    </table>

	<?php if (!empty($user->videos)): ?>
        <div class="">
            <h4><?= __('Uploaded Videos') ?></h4>
            <table cellpadding="0" cellspacing="0">
                <tbody>
				<?php foreach ($user->videos as $video): ?>
                    <tr class="video_props">
                        <td rowspan="4" class="video_preview_cell">
							<?php
							$helper = new VideoViewHelper();
							$view_url = $this->Url->build(['action' => 'view', $video->id]);
							echo $helper->preview($video, 0, $video->status, $view_url);
							?>
                        </td>
                        <td colspan="5" style="text-align:left; padding:0;">
                            <h5 style="margin-bottom: 0;">
								<?= h($video->id) ?>.
								<?= $this->Html->link(h($video->caption), ['controller' => 'Videos', 'action' => 'view', $video->id]) ?>
                            </h5>
                            <span class="video_props_svid"><?= h($video->svid) ?></span>
                        </td>
                    </tr>
                    <tr class="video_props">
                        <td class="video_props video_props_header"><?= __('Video Mode') ?></td>
                        <td class="video_props video_props_header"><?= __('Created') ?></td>
                        <td class="video_props video_props_header"><?= __('Last modified') ?></td>
                        <td class="video_props video_props_header"><?= __('Status') ?></td>
                    </tr>
                    <tr class="video_props">
                        <td class="video_props"><?= VideoViewHelper::mode($video->mode) ?></td>
                        <td class="video_props"><?= h($video->created) ?></td>
                        <td class="video_props"><?= h($video->modified) ?></td>
                        <td class="video_props"><?= VideoViewHelper::status($video->status) ?></td>
                        <!--
                <td class="actions">
					<?= $this->Html->link(__('View'), ['action' => 'view', $video->id]) ?>
					<?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $video->id], ['confirm' => __('Are you sure you want to delete # {0}?', $video->id)]) ?>
                </td>
                -->
                    </tr>
                    <tr>
                        <td colspan="5" style="height:100px;"></td>
                    </tr>
				<?php endforeach; ?>
                </tbody>
            </table>
        </div>
	<?php endif; ?>
</div>
