<?php
/**
 * OpenSmart :
 * Copyright (c) Smart Consortium. (https://smart-consortium.org)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author        masahiro ehara <masahiro.ehara@irona.co.jp>
 * @copyright     Copyright (c) Smart Consortium. (https://smart-consortium.org)
 * @link          https://smart-consortium.org OpenSmart Project
 * @since         0.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use App\Utility\Server;
use Cake\Core\Configure;
use View\Helper\FormHelper;
use View\Helper\HtmlHelper;

if(Server::is_encoding_server_mode()){
	echo $this->Form->create(
		'null',
		['type'    => 'post',
		 'url'     => ['controller' => 'videos',
		               'action'     => 'post'],
		 'enctype' => 'multipart/form-data']
	);
} else {
	echo $this->Form->create(
		'null',
		['type'    => 'post',
		 'url'     => Server::encoding_server() . DS . 'videos' . DS . 'post',
		 'enctype' => 'multipart/form-data']
	);
}
?>
<script type="text/javascript">

</script>

<div>
    <h5>
		<?= $this->Form->control('caption', ['type' => 'text', 'label' => __('caption')]) ?>
    </h5>
</div>
<div>
    <h6>Video files</h6>
</div>
<table>
    <tr>
        <th style="width:100px;"><?= __('Camera No.'); ?></th>
        <th><?= __('Play Forward (Default)'); ?></th>
        <th style="width:150px;"><?= __('Options'); ?></th>
        <th></th>
    </tr>

	<?php
	for ($i = 0; $i < Configure::read('System.max_camera'); $i++) {
		?>
        <tr>
            <td rowspan="5">
				<?= ($i + 1) ?>
            </td>
        </tr>
        <tr>
            <td rowspan="4">
				<?= $this->Form->control("play_forward[$i]", ['type' => 'file', 'label' => __('file')]); ?>
            </td>
        </tr>
        <tr>
            <td style="color: rgb(50, 97, 171);">
				<?= __('Slow Forward'); ?>
            </td>
            <td>
				<?= $this->Form->control("slow_forward[$i]", ['type' => 'file', 'label' => __('file')]); ?>
            </td>
        </tr>
        <tr>
            <td style="color: rgb(50, 97, 171);">
				<?= __('Play Reverse'); ?>
            </td>
            <td>
				<?= $this->Form->control("play_reverse[$i]", ['type' => 'file', 'label' => __('file')]); ?>
            </td>
        </tr>
        <tr>
            <td style="color: rgb(50, 97, 171);">
				<?= __('Slow Reverse'); ?>
            </td>
            <td>
				<?= $this->Form->control("slow_reverse[$i]", ['type' => 'file', 'label' => __('file')]); ?>
            </td>
        </tr>
		<?php
	}
	?>
</table>

<?= $this->Form->button(__('upload')); ?>
<?= $this->Form->end(); ?>
