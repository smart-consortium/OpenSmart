<?php
/**
 * OpenSmart :
 * Copyright (c) Smart Consortium. (https://smart-consortium.org)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Smart Consortium. (https://smart-consortium.org)
 * @link          https://smart-consortium.org OpenSmart Project
 * @since         0.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Service;


/**
 * Interface IBuilder
 * @package App\Service
 */
interface IBuilder
{
	/**
	 * Execute batch build
	 * @param string $svid SVID
	 * @param array  $targets Original video file list
	 * @return mixed
	 */
	public function build(string $svid, array $targets);
}