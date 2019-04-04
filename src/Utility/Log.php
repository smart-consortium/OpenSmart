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

namespace App\Utility;

use Cake\Log\Log as CakeLog;

/**
 * Class Log
 * Wrapper class of Cake\Log\Log with some utility functions.
 *
 * @package App\Utility
 */
class Log
{

	/**
	 * Convenience implementation of Cake\Log\Log::write()
	 *
	 * @see Cake\Log\Log::write()
	 * @param      $level
	 * @param      $message
	 * @param null $args
	 * @param int  $depth
	 * @return bool
	 */
	public static function write($level, $message, $args = null, $depth = 0)
	{
		if (is_array($message)) {
			$msg = print_r($message, true);
		} else {
			$msg = __d('log_msg', $message, $args);
			if (self::__is_print_caller($level)) {
				$caller = Log::_caller($depth);
				$msg = '[' . $caller . '] ' . $msg;
			}
		}
		return CakeLog::write($level, $msg, []);
	}


	/**
	 * Convenience method to log emergency messages
	 *
	 * @see Cake\Log::emergency()
	 * @param      $message
	 * @param null $args
	 * @param int  $depth
	 * @return bool
	 */
	public static function emergency($message, $args = null, $depth = 0)
	{
		return static::write(__FUNCTION__, $message, $args, $depth);
	}

	/**
	 * Convenience method to log alert messages
	 *
	 * @see Cake\Log\Log::alert()
	 * @param      $message
	 * @param null $args
	 * @param int  $depth
	 * @return bool
	 */
	public static function alert($message, $args = null, $depth = 0)
	{
		return static::write(__FUNCTION__, $message, $args, $depth);
	}

	/**
	 * Convenience method to log critical messages
	 *
	 * @see Cake\Log\Log::critical()
	 * @param      $message
	 * @param null $args
	 * @param int  $depth
	 * @return bool
	 */
	public static function critical($message, $args = null, $depth = 0)
	{
		return static::write(__FUNCTION__, $message, $args, $depth);
	}

	/**
	 * Convenience method to log error messages
	 *
	 * @see Cake\Log\Log::error()
	 * @param      $message
	 * @param null $args
	 * @param int  $depth
	 * @return bool
	 */
	public static function error($message, $args = null, $depth = 0)
	{
		return static::write(__FUNCTION__, $message, $args, $depth);
	}

	/**
	 * Convenience method to log warning messages
	 *
	 * @see Cake\Log\Log::warning()
	 * @param      $message
	 * @param null $args
	 * @param int  $depth
	 * @return bool
	 */
	public static function warning($message, $args = null, $depth = 0)
	{
		return static::write(__FUNCTION__, $message, $args, $depth);
	}

	/**
	 * Convenience method to log notice messages
	 *
	 * @see Cake\Log\Log::notice()
	 * @param      $message
	 * @param null $args
	 * @return bool
	 */
	public static function notice($message, $args = null)
	{
		return static::write(__FUNCTION__, $message, $args);
	}

	/**
	 * Convenience method to log debug messages
	 *
	 * @see Cake\Log\Log::debug()
	 * @param      $message
	 * @param null $args
	 * @param int  $depth
	 * @return bool
	 */
	public static function debug($message, $args = null, $depth = 0)
	{
		if (is_array($message)) {
			return static::write(__FUNCTION__, print_r($message, true), $args, $depth);
		} else {
			return static::write(__FUNCTION__, $message, $args, $depth);
		}
	}

	/**
	 * Convenience method to log info messages
	 *
	 * @see Cake\Log\Log::info()
	 * @param      $message
	 * @param null $args
	 * @return bool
	 */
	public static function info($message, $args = null)
	{
		return static::write(__FUNCTION__, $message, $args);
	}

	private static function _caller($depth = 0)
	{
		$trace = debug_backtrace($limit = (4 + $depth));
		$class = $trace[(3 + $depth)]['class'];
		$function = $trace[(3 + $depth)]['function'];
		$line = $trace[(3 + $depth)]['line'];
		return "$class::$function($line)";
	}

	/**
	 * @param $level
	 * @return bool
	 */
	public static function __is_print_caller($level)
	{
		$levels = ['emergency', 'alert', 'critical', 'error', 'warning', 'debug'];
		return in_array($level, $levels);
	}

	/**
	 * Get processing time by milli second.
	 * @param float $start started time (micro second)
	 * @param float $end end time (micro second)
	 * @return float processing time ( ** milli ** second)
	 */
	public static function processing_time_ms($start, $end)
	{
		$ms = ($end - $start) * 1000;
		return round($ms, 2);
	}
}
