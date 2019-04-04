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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Core\Configure;
use Cake\Core\Exception\MissingPluginException;
use Cake\Core\Plugin;
use Cake\Log\Log;

// monolog
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Monolog\Formatter\LineFormatter;

/**
 * Additional bootstrapping and configuration for CLI environments should
 * be put here.
 */

// Set the fullBaseUrl to allow URLs to be generated in shell tasks.
// This is useful when sending email from shells.
//Configure::write('App.fullBaseUrl', php_uname('n'));

/*
 * Configuration of logger
 */
// Set logs to different files so they don't have permission conflicts.
//Configure::write('Log.debug.file', 'cli-debug');
//Configure::write('Log.error.file', 'cli-error');
Log::setConfig('cli', function () {
    $log = new Logger('cli');
    $logformat = "[%level_name%] [%datetime%] %message% %extra%\n";
    $formatter = new LineFormatter($logformat, null, true, true);
    $stream = new RotatingFileHandler(LOGS . 'cli.log', 10, Logger::DEBUG, false, 0777, false);
    $stream->setFormatter($formatter);
    $handler = new FingersCrossedHandler($stream, new ErrorLevelActivationStrategy(Logger::DEBUG));
    $log->pushHandler($handler);
    return $log;
});

// Optionally stop using the now redundant default loggers
Log::drop('info');
Log::drop('debug');
Log::drop('error');

try {
    Plugin::load('Bake');
} catch (MissingPluginException $e) {
    // Do not halt if the plugin is missing
}

Plugin::load('Migrations');
