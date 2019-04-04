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

const FFMPEG_MAKE_IMAGE = 'ffmpeg -y -ss %s -i %s -r 1 -vframes %d -f image2 -s %s %s';

const FFMPEG_PROCESS_CHECK = "ps aux | grep 'ffmpeg '";

const FFMPEG_TRANSCODE = 'ffmpeg -i %s -movflags faststart -vcodec libx264 -acodec aac %s';

const FFMPEG_VIDEO_PADDING = 'ffmpeg -i %s -vcodec libx264 -acodec aac -vf "pad=%d:0:%d:0:#484848" %s';

const SHELL_NAME_JOB_MANAGER = 'jobManager';

const TASK_NAME_WORKER = 'worker';

const JOB_MANAGER_PROCESS_CHECK = "ps aux | pgrep -fl 'bin/cake.php " . SHELL_NAME_JOB_MANAGER . "'$";

const WORKER_PROCESS_CHECK = "ps aux | pgrep -fl 'bin/cake.php " . SHELL_NAME_JOB_MANAGER . " " . TASK_NAME_WORKER . "'";
