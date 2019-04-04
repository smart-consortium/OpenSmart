<?php
/**
 * @var \App\View\AppView           $this
 * @var \App\Model\Entity\Parameter $parameter
 */

?>
<div class="parameters view large-9 medium-8 columns content">
    <h3>params.json</h3>
    <pre><?= h($parameter->body) ?></pre>
    <ul>
        <li class="link_button edit_button"><?= $this->Html->link(__('Edit'), ['action' => 'edit', $parameter->id]) ?> </li>
    </ul>
</div>
