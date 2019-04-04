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

namespace App\Service\Model;


use App\Utility\Log;

class CakeProcess extends Process
{

	/**
	 * CakeProcess constructor.
	 *
	 * @param string $command
	 * @param array  $args
	 */
	public function __construct(string $command, array $args)
	{
		parent::__construct($command, $args);
		$this->command = $this->_cake_command($command, $args);
		Log::debug('Create command => ' . $this->command);
	}

	/**
	 * Make CakePHP shell command string
	 * Attention:: This method provide No validation and No security check.
	 *
	 * @param string $command_name shell command name. eg. 'bin/cake bake'
	 * @param array  $args Key-Value array of shell command option. eg. ['hoge' => 'fuga']
	 *                     Value can be set an array. eg ['hoge' => ['fuga', 'piyo']]
	 * @return string Shell command.
	 */
	private function _cake_command(string $command_name, array $args): string
	{
		$command = [BIN_CAKE, $command_name];

		foreach ($args as $key => $value) {
			if (is_array($value)) {
				foreach ($value as $v) {
					$command[] = "--$key=$v";
				}
			} else {
				$command[] = "--$key=$value";
			}
		}
		return implode(' ', $command);
	}

}