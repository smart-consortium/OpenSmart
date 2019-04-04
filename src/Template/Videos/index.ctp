<?php
/**
 * @var \App\View\AppView                                              $this
 * @var \App\Model\Entity\Video[]|\Cake\Collection\CollectionInterface $videos
 */

use App\View\Helper\VideoViewHelper;

$this->assign('title', __('App Name'));
?>

<div class="videos index large-10 medium-8 columns content">
    <div style="float: right">
        <input id="view_all" type="checkbox" title="<?= __('View all videos') ?>"/><?= __('View all videos') ?>
    </div>
    <div class="col_5">
		<?php foreach ($videos as $video): ?>
            <div>
				<?php
				$helper = new VideoViewHelper();
				$view_url = $this->Url->build(['action' => 'view', $video->id]);
				echo $helper->preview($video, 0, $video->status, $view_url);
				?>
				<?= h($video->id) ?>.
				<?= VideoViewHelper::mode($video->mode) ?>
				<div class="caption">
					<?= $this->Html->link(h($video->caption), ['action' => 'view', $video->id]) ?>
                </div>
                <div>
					<?= $video->has('user') ? $this->Html->link($video->user->username, ['controller' => 'Users', 'action' => 'view', $video->user->id]) : '' ?>
					<?= h($video->modified) ?>
                </div>
            </div>
		<?php endforeach; ?>
    </div>
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
