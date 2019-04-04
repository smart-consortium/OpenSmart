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
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Controller;

use Cake\Http\Exception\BadRequestException;
use Cake\Validation\Validator;

class BuildController extends AppController
{

	const HTTP_QUERY_SVID = 'svid';

	public function index()
	{
		$this->autoRender = false;
		$svid = $this->_get_queries_or_fail();


	}

	private function _get_queries_or_fail(): array
	{
		$validator = new Validator();
		$validator->requirePresence(self::HTTP_QUERY_SVID)
		          ->notEmpty(self::HTTP_QUERY_SVID, __('Missing target SVID'))
		          ->url(self::HTTP_QUERY_SVID, __('Illegal SVID format'));
		$errors = $validator->errors($this->request->getQueryParams());

		if (empty($errors)) {
			$svid = $this->request->getQuery(self::HTTP_QUERY_SVID);
			return $svid;
		} else {
			$msg = '';
			foreach ($errors as $field => $error) {
				$msg .= $field . ' => ';
				foreach ($error as $index => $item) {
					$msg .= $item;
					$msg .= PHP_EOL;
				}
			}
			throw new BadRequestException($msg);
		}
	}
}