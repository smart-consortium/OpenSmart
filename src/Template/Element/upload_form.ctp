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

use View\Helper\HtmlHelper;
use View\Helper\FormHelper;
use \Cake\Core\Configure;

$max_cameras = Configure::read('System.max_camera');

echo $this->Form->create(
	'null',
	['type'    => 'post',
	 'url'     => ['controller' => 'videos',
	               'action'     => 'post'],
	 'enctype' => 'multipart/form-data']
);

?>
<script type="text/javascript">

</script>
<table>
    <tr>
        <th style="width:100px;"><?= __('Camera No.'); ?></th>
        <th><?= __('Play Forward (Default)'); ?></th>
        <th><?= __('Slow Forward'); ?></th>
    </tr>

	<?php for ($i = 0; $i < $max_cameras; $i++) { ?>
        <tr>
            <td>
		        <?= ($i + 1) ?>
            </td>
            <td>
				<?= $this->Form->input("play_forward[$i]", ['type' => 'file', 'label' => __('file')]); ?>
            </td>
            <td>
				<?= $this->Form->input("slow_forward[$i]", ['type' => 'file', 'label' => __('file')]); ?>
            </td>
        </tr>
	<?php } ?>
</table>

<?= $this->Form->button(__('upload')); ?>
<?= $this->Form->end(); ?>
