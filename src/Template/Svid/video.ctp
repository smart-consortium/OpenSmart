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
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Back'), ['controller' => 'videos', 'action' => 'index']) ?> </li>
    </ul>
</nav>
<div class="svid videos large-9 medium-8 columns content">
    <h3>SVID</h3>
    <pre><?= $svid ?></pre>
</div>
