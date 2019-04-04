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

namespace App\Shell;

use Cake\Console\Shell;
use Cake\Datasource\ConnectionManager;
use Cake\Filesystem\File;
use Cake\Filesystem\Folder;
use Cake\ORM\TableRegistry;

class CleanupShell extends Shell
{
	public function main()
	{
		{
			$folder = new Folder(LOGS);
			$files = $folder->find('.*');
			foreach ($files as $file) {
				$file = new File(LOGS . $file);
				$file->delete();
			}
		}

		{
			$folder = new Folder(VIDEOS);
			if ($folder->delete()) {
				$folder->create(VIDEOS);
			}
		}
		{
			$table = TableRegistry::get('Relations');
			$table->query()
			      ->delete()
			      ->execute();
		}
		{
			$table = TableRegistry::get('Jobs');
			$table->query()
			      ->delete()
			      ->execute();
			}
		{
			$table = TableRegistry::get('Parameters');
			$table->query()
			      ->delete()
			      ->execute();
		}
		{
			$table = TableRegistry::get('Videos');
			$table->query()
			      ->delete()
			      ->execute();
		}
		$connection = ConnectionManager::get('default');
		$connection->execute('ALTER TABLE relations auto_increment = 1');
		$connection->execute('ALTER TABLE jobs auto_increment = 1');
		$connection->execute('ALTER TABLE parameters auto_increment = 1');
		$connection->execute('ALTER TABLE videos auto_increment = 1');
	}
}