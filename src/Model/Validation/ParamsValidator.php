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

namespace App\Model\Validation;
// memo
// https://github.com/justinrainbow/json-schema

use JsonSchema\Validator;

class ParamsValidator
{

	public function __construct()
	{

	}

	public static function validate(array $params): bool{
		$validator = new Validator();
		return false;
	}
}