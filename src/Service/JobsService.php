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

namespace App\Service;

/**
 *  * Class TransferService
 * @package App\Service
 * @property \App\Model\Table\JobsTable $Jobs
 */
class JobsService extends AppService
{
	/**
	 * @param array $options
	 * @return bool
	 * @throws \Exception
	 */
	public function queue(array $options): bool
	{
		$service = new SvidService($this->svid);

		$entity = $this->Jobs->newEntity();
		$entity->video_id = $service->get_video_id();
		$entity->name = $options['name'];
		$entity->command = $options['command'];

		if ($this->Jobs->save($entity)) {
			if ($entity->id > 0) {
				return CODE_SUCCESS;
			} else {
				$this->abort('Add job failed');
			}
		}
		$this->abort('Add job failed');
	}
}