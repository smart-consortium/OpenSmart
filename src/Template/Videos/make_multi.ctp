<?php
/**
 * OpenSmart :
 * Copyright (c) Smart Consortium. (https://smart-consortium.org)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author      masahiro ehara <masahiro.ehara@irona.co.jp>
 * @copyright   Copyright (c) Smart Consortium. (https://smart-consortium.org)
 * @link        https://smart-consortium.org OpenSmart Project
 * @since       1.0.0
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * @var \App\View\AppView                                              $this
 * @var \App\Model\Entity\Video[]|\Cake\Collection\CollectionInterface $videos
 */

use App\View\Helper\VideoViewHelper;

$this->assign('title', __('App Name'));
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Add new video'), ['action' => 'upload']) ?></li>
    </ul>
</nav>
<div class="videos index large-10 medium-8 columns content">
    <h3><?= __('Make multi camera video') ?></h3>

    <table cellpadding="0" cellspacing="0">
        <thead>
        <tr>
            <th scope="col"><?= $this->Paginator->sort('id', 'ID') ?></th>
            <th scope="col"><?= $this->Paginator->sort('caption', 'Caption') ?></th>
            <th scope="col" style="width:20%;"><?= $this->Paginator->sort('user_id') ?></th>
            <th scope="col" style="width:10%;"><?= $this->Paginator->sort('mode', __('Mode')) ?></th>
            <th scope="col" style="width:15%;"><?= $this->Paginator->sort('created') ?></th>
            <th scope="col" style="width:15%;"><?= $this->Paginator->sort('modified') ?></th>
        </tr>
        </thead>
        <tbody>
		<?php foreach ($videos as $video): ?>
            <tr>
                <td>
					<?php
					echo $this->Number->format($video->id);
					$helper = new VideoViewHelper();
					$view_url = $this->Url->build(['action' => 'view', $video->id]);
					echo $helper->preview($video, 0, $video->status, $view_url);
					?>
                </td>
                <td><?= h($video->caption) ?></td>
                <td><?= $video->has('user') ? $this->Html->link($video->user->name, ['controller' => 'Users', 'action' => 'view', $video->user->id]) : '' ?></td>
                <td><?= VideoViewHelper::mode($video->mode) ?></td>
                <td><?= h($video->created) ?></td>
                <td><?= h($video->modified) ?></td>
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
