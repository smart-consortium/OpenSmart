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

namespace App\Test\TestCase\Service\Model;


use App\Exception\InvalidArgumentException;
use App\Service\Model\ParamsJson;
use Cake\TestSuite\TestCase;

class ParamsJsonTest extends TestCase
{
	public function setUp()
	{
	}
	
	public function test_init(){
		try{
			new ParamsJson([]);
		} catch (InvalidArgumentException $ex){
			self::assertTrue(true);
			return;
		}
		self::fail('No Exception');
	}
}