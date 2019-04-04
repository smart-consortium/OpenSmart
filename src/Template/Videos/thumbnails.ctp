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
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Video $video
 */
use App\View\Helper\VideoViewHelper;
?>

<div class="videos view large-12 medium-8 columns content">
    <div id="video_container">

        <h5><?= $this->Number->format($video->id) ?> . <?= h($video->caption) ?></h5>
        <table class="vertical-table video_props">
            <tr>
                <th scope="row" class="video_props_header"><?= __('User') ?></th>
                <td><?= $video->has('user') ? $this->Html->link($video->user->first_name . ' ' . $video->user->family_name, ['controller' => 'Users', 'action' => 'view', $video->user->id]) : '' ?></td>
            </tr>
            <tr>
                <th scope="row" class="video_props_header"><?= __('SVID') ?></th>
                <td><?= $this->Html->link($video->svid, $video->svid . '?display=html') ?></td>
            </tr>
            <tr>
                <th scope="row" class="video_props_header"><?= __('Mode') ?></th>
                <td><?= VideoViewHelper::mode($video->mode) ?></td>
            </tr>
            <tr>
                <th scope="row" class="video_props_header"><?= __('Created') ?></th>
                <td><?= h($video->created) ?></td>
            </tr>
            <tr>
                <th scope="row" class="video_props_header"><?= __('Modified') ?></th>
                <td><?= h($video->modified) ?></td>
            </tr>
        </table>
    </div>
</div>
