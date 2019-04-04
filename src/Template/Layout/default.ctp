<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

$this->assign('title', __('App Name'));
?>
<!DOCTYPE html>
<html>
<head>
	<?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
		<?= $this->fetch('title') ?>
    </title>
	<?= $this->Html->meta('icon') ?>

	<?= $this->Html->css('base.css') ?>
	<?= $this->Html->css('cake.css') ?>
	<?= $this->Html->css('opensmart.css') ?>

	<?= $this->Html->script('bundle.js') ?>
	<?= $this->Html->script('opensmart.js') ?>

	<?= $this->fetch('meta') ?>
	<?= $this->fetch('css') ?>
	<?= $this->fetch('script') ?>
</head>
<body>
<nav class="top-bar expanded" data-topbar role="navigation">
    <ul class="title-area large-2 medium-4 columns">
        <li class="name">
            <h1><a href="/"><?= $this->fetch('title') ?></a></h1>
        </li>
    </ul>
    <div class="top-bar-section">
        <ul class="right">
            <li>
				<?= $this->Html->link('Upload', [
					'controller' => 'Videos',
					'action' => 'upload'],
                                      ['class' => 'btn']
                ); ?>
            </li>
            <li>
	            <?= $this->Html->link('Job List', [
		            'controller' => 'Jobs',
		            'action' => 'index'],
	                                  ['class' => 'btn']
	            ); ?>
            </li>
            <li>
		        <?= $this->Html->link('Preferences', [
			        'controller' => 'Preferences',
			        'action' => 'index'],
		                              ['class' => 'btn']
		        ); ?>
            </li>
            <li>
		        <?= $this->Html->link('Sign out', [
			        'controller' => 'Users',
			        'action' => 'logout'],
		                              ['class' => 'btn']
		        ); ?>
            </li>
        </ul>
    </div>
</nav>
<?= $this->Flash->render() ?>
<div class="container clearfix">
    <?php
        if(\App\Utility\Server::is_web_server_mode()){
	        echo $this->fetch('content');
        } else {
            echo 'This is not web server';
        }
    ?>
</div>
<footer>
</footer>
</body>
</html>
