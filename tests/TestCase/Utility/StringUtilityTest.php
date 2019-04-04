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

namespace App\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use App\Utility\StringUtility;

class StringUtilityTest extends TestCase
{
	public function setUp()
	{
	}

	public function testStartsWith()
	{
		$test = StringUtility::startsWith('hogefuga', 'hoge');
		$this->assertTrue($test);
	}

	public function testEndsWith()
	{
		$test = StringUtility::endsWith('hogefuga', 'fuga');
		$this->assertTrue($test);
	}

	public function testHoge()
	{
		self::assertEquals(1, preg_match('/^[1-9]\d*/', '299k', $result));
		self::assertEquals(299, $result[0]);
	}
}
