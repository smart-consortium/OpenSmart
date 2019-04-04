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
 * @version       1.0.0
 * @since         0.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

// Constants of directory names
const VIDEO_DIR = 'video';
const ORIGINALS_DIR = 'originals';
const THUMBNAIL_DIR = 'thumbnails';
const STILL_DIR = 'stills';
const PREVIEW_DIR = 'preview';
const HLS_DIR = 'HLS';
const HLS_MAIN_FILE = 'main.m3u8';
const HLS_SUB_FILE = 'sub.m3u8';

/**
 * Path to the OpenSmart directory.
 */
const VIDEOS = WWW_ROOT . VIDEO_DIR . DS;

# Constants of file names
const PARAMS_JSON_FILE_NAME = 'params.json';
const PARAMS_JSON_FILE_NAME_DEFAULT = PARAMS_JSON_FILE_NAME;
const PARAMS_JSON_FILE_NAME_MULTI = 'multi.json';
const PARAMS_JSON_FILE_NAME_TRICK = 'trick.json';
const ENCODE_LOG_FILE_NAME = '.encode.log';
const ENCODE_PROGRESS_FILE_NAME = '.encode_progress.log';
const MEDIA_INFO_FILE_NAME = 'media_info.json';

/**
 * Time list file name
 *
 * @var string
 */
const TIME_LIST_JSON_FILE_NAME = 'time_list.json';

/**
 * Time forward file name
 *
 * @var string
 */
const TIME_FORWARD_JSON_FILE_NAME = 'time_forward.json';


const ENCODE_TYPE_HLS = 'HLS';
const SVID = 'svid';

const CONFIG_OPEN_SMART_DIR = CONFIG . 'OpenSmart' . DS;
const CONFIG_OPEN_SMART_RESOURCES_DIR = CONFIG_OPEN_SMART_DIR . 'resources' . DS;
const CONFIG_OPEN_SMART_PARAMS_DIR = CONFIG_OPEN_SMART_RESOURCES_DIR . 'params' . DS;
