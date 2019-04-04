<?php
/**
 * OpenSmart : definision file.
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

const APP_TITLE = 'OpenSmart';

## public Web API name
const API_BASE = DS. 'api' . DS;
const API_PICTURE = 'picture';
const API_PICTURE_MODE_STILL = 'still';
const API_PICTURE_MODE_THUMBNAIL = 'thumbnail';
const API_PICTURE_MODE_PREVIEW = 'preview';

const API_HLS = 'hls';
const API_HLS_META_MAIN = API_HLS . DS . 'main';
const API_HLS_META_SUB = API_HLS . DS . 'sub';

## POST or GET Request key ##
const REQUEST_KEY_PLAY_FORWARD = 'play_forward';
const REQUEST_KEY_SLOW_FORWARD = 'slow_forward';
const REQUEST_KEY_PLAY_REVERSE = 'play_reverse';
const REQUEST_KEY_SLOW_REVERSE = 'slow_reverse';
const REQUEST_VIDEO_TYPE_LIST = [REQUEST_KEY_PLAY_FORWARD,
                                 REQUEST_KEY_SLOW_FORWARD,
                                 REQUEST_KEY_PLAY_REVERSE,
                                 REQUEST_KEY_SLOW_REVERSE];
const REQUEST_KEY_CAMERA = 'camera';
const REQUEST_FILE_KEY_NAME = 'name';
const REQUEST_FILE_KEY_ERROR = 'error';

## Session ##
const SESSION_ACCESS_TOKEN = 'OpenSmart.access_token';

## Service result ##
const CODE_SUCCESS = true;
const CODE_FAILED = false;

## Service result keys ##
const RESULT_KEY_STATUS = 'status';
const RESULT_KEY_MSG = 'message';
const RESULT_KEY_MSG_DETAIL = 'detail';
const RESULT_KEY_VIDEO_ID = 'video_id';
const RESULT_KEY_VIDEO_DIR = 'video_dir';
const RESULT_KEY_TARGETS = 'targets';
const RESULT_KEY_SVID = 'svid';
const RESULT_KEY_DATA = 'data';


## Shell command common option names ##
const SHELL_OPTION_ACCESS_TOKEN = 'access_token';
const SHELL_OPTION_SVID = 'svid';

## Shell command names ##
const SHELL_COMMAND_NAME_BUILD = 'build';
const SHELL_COMMAND_NAME_PRE_BUILD = 'pre_build';
const SHELL_COMMAND_NAME_ENCODE = 'encode';
const SHELL_COMMAND_NAME_DEPLOY = 'deploy';
const SHELL_COMMAND_NAME_BUNDLE = 'bundle';
const SHELL_COMMAND_NAME_HLS = 'hls';

## Shell command options ##
const SHELL_OPTION_ID = 'id';
const SHELL_OPTION_REBUILD = 'rebuild';
const SHELL_OPTION_NORMALIZE = 'normalize';
const SHELL_OPTION_TAG = 'tag';
const SHELL_OPTION_TARGETS = 'targets';
const SHELL_OPTION_TARGET_PLAY_FORWARD = REQUEST_KEY_PLAY_FORWARD;
const SHELL_OPTION_TARGET_SLOW_FORWARD = REQUEST_KEY_SLOW_FORWARD;
const SHELL_OPTION_TARGET_PLAY_REVERSE = REQUEST_KEY_PLAY_REVERSE;
const SHELL_OPTION_TARGET_SLOW_REVERSE = REQUEST_KEY_SLOW_REVERSE;
const SHELL_OPTION_TARGET_TYPE_LIST = REQUEST_VIDEO_TYPE_LIST;
const SHELL_OPTION_CAMERA_MODE = 'camera_mode';
const SHELL_OPTION_VIDEO = 'video';
const SHELL_OPTION_INPUT_FILE = 'input_file';
const SHELL_OPTION_OUTPUT_DIR = 'output_dir';
const SHELL_OPTION_ENCODE_TYPE = 'encode_type';
const SHELL_OPTION_BIT_RATE_VIDEO = 'bit_rate_video';
const SHELL_OPTION_BIT_RATE_AUDIO = 'bit_rate_audio';
const SHELL_OPTION_VIDEO_SIZE = 'video_size';
const SHELL_OPTION_CAMERA = 'camera';
const SHELL_OPTION_NAME = 'name';
const SHELL_OPTION_EMAIL = 'email';
const SHELL_OPTION_STATUS = 'status';

## Shell command suffix for background execute ##
const BACKGROUND_EXEC_PREFIX = 'nohup ';
const BACKGROUND_EXEC_GET_PID = ' & echo $!';
const BACKGROUND_EXEC_SUFFIX = ' > /dev/null';

## params.json key ##
const PARAMS_TAG_BUILD = 'build';
const PARAMS_TAG_ENCODE = 'encode';
const PARAMS_TAG_DEPLOY = 'deploy';
const PARAMS_TAG_VERSION = 'version';
const PARAMS_TAG_CAMERA = 'camera';
const PARAMS_TAG_CAPTION = 'caption';
const PARAMS_TAG_COMMAND = 'command';
const PARAMS_TAG_TAG = 'tag';
const PARAMS_TAG_SVID = 'svid';
const PARAMS_TAG_OPTIONS = 'options';
const PARAMS_TAG_TARGET = 'target';
const PARAMS_TAG_VIDEOS = 'videos';
const PARAMS_TAG_PLAY_FORWARD = 'playForward';
const PARAMS_TAG_SLOW_FORWARD = 'slowForward';
const PARAMS_TAG_PLAY_REVERSE = 'playReverse';
const PARAMS_TAG_SLOW_REVERSE = 'slowReverse';
const PARAMS_TAG_VIDEO_TYPE_LIST = [PARAMS_TAG_PLAY_FORWARD,
                                    PARAMS_TAG_SLOW_FORWARD,
                                    PARAMS_TAG_PLAY_REVERSE,
                                    PARAMS_TAG_SLOW_REVERSE];

const PARAMS_TAG_NAME = 'name';
const PARAMS_TAG_VTRACK = 'vtrack';
const PARAMS_TAG_DURATION = 'Duration';
const PARAMS_TAG_duration = 'duration';
const PARAMS_TAG_BIT_RATES = 'bit_rates';
const PARAMS_TAG_BIT_RATE_VIDEO = 'video';
const PARAMS_TAG_BIT_RATE_AUDIO = 'audio';
const PARAMS_TAG_HLS = 'HLS';
const PARAMS_TAG_SRC_RANGE = 'srcRange';
const PARAMS_TAG_DST_RANGE = 'dstRange';
const PARAMS_TAG_TYPE = 'type';
const PARAMS_TAG_STILL = 'still';
const PARAMS_TAG_THUMBNAIL = 'thumbnail';
const PARAMS_TAG_TIMES = 'times';
const PARAMS_TAG_SIZE = 'size';
const PARAMS_TAG_SOURCE = 'source';
const PARAMS_TAG_STATUS = 'status';
const PARAMS_TAG_METHOD = 'method';
const PARAMS_TAG_RANGE = 'range';
const PARAMS_TAG_TOTAL = 'total';
const TIME_LIST_METHOD_COMPUTE = 'compute';
const TIME_LIST_METHOD_MANUAL = 'manual';

## SVID key ##
const SVID_TAG_CAMERA = PARAMS_TAG_CAMERA;
const SVID_TAG_CAPTION = PARAMS_TAG_CAPTION;
const SVID_TAG_STATUS = PARAMS_TAG_STATUS;
const SVID_TAG_VERSION = PARAMS_TAG_VERSION;
const SVID_TAG_VIDEO = 'video';
const SVID_TAG_duration = PARAMS_TAG_duration;

## HLS
/**
 * HLS manifest version
 */
const HLS_EXT_X_VERSION_NUMBER = 3;
const HLS_COPYRIGHT_HEADER = '## Created with OpenSmart';

const HLS_MANIFEST_TYPE = '#EXTM3U';
const HLS_EXT_X_VERSION = '#EXT-X-VERSION';
const HLS_EXT_INF = '#EXTINF';
const HLS_EXT_INF_HEADER = HLS_EXT_INF . ':';
const HLS_EXT_X_DISCONTINUITY = '#EXT-X-DISCONTINUITY';
const HLS_EXT_X_ENDLIST = '#EXT-X-ENDLIST';

## DB setting ##
const TABLE_NAME_SERVERS = 'Servers';
const TABLE_NAME_USERS = 'Users';
const TABLE_NAME_VIDEOS = 'Videos';
const TABLE_NAME_PARAMETERS = 'Parameters';
const TABLE_NAME_JOBS = 'Jobs';
const TABLE_NAME_RELATIONS = 'Relations';

## VIDEOS ##
const DEFAULT_VIDEO_EXT = 'mp4';

/**
 * Simple video flag
 *
 * @var int
 */
const CAMERA_MODE_SINGLE = 0;

/**
 * Trick video flag
 *
 * @var int
 */
const CAMERA_MODE_TRICK = 1;


/**
 * Multi camera video flag
 *
 * @var int
 */
const CAMERA_MODE_MULTI = 2;

/**
 * Multi camera video svid suffix
 * @var string
 */
const MULTI_CAMERA_SVID_SUFFIX = 'M';
/**
 * Trick camera video svid suffix
 * @var string
 */
const TRICK_CAMERA_SVID_SUFFIX = 'T';

# BUILD
const BUILD_STATUS_FAILED = -1;
const BUILD_STATUS_WAITING = 0;
const BUILD_STATUS_BUILDING = 1;
const BUILD_STATUS_SUCCESS = 2;
const BUILD_STATUS_NEED_REBUILD = 3;

## Movie and Pictures ##
/**
 * Image file extension
 */
const DEFAULT_IMG_EXT = 'jpg';

/**
 * Movie segment file's extension
 */
const DEFAULT_SEGMENT_FILE_EXT = 'ts';

/**
 * NTSC format video fps (Exactly : 29.97)
 *
 * @var int
 */
const NTSC_FPS = 30;

/**
 * picture size delimiter.
 * ex) 720x360
 */
const SIZE_DELIMITER = 'x';

## Tasks and Jobs ##
const DEFAULT_WAIT_TIME = 10;

const DEFAULT_CONCURRENT_WORKERS = 2;

const JOB_STATUS_FAILED = -1;
const JOB_STATUS_WAIT = 0;
const JOB_STATUS_PROCESSING = 1;
const JOB_STATUS_OK = 2;
const JOB_NAME_BUILD = 'build';
const JOB_NAME_REBUILD = 'rebuild';

/**
 * Temporary file prefix
 *
 * @var string
 */
const TMP_PREFIX = 'TMP';

## Authentications ##
const CAKE_AUTH_PREFIX = DS . VIDEO_DIR . DS;