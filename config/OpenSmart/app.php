<?php
/**
 * OpenSmart : definition file.
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

return [
	'System' => [
		'web_server'               => 'http://localhost',
		'storage_server'           => 'http://localhost',
		'encoding_server'          => 'http://localhost',
		'max_camera'               => 8,
		'concurrent_workers_limit' => 2,
		'ffmpeg_process_limit'     => 3,
		'process_wait'             => 10,
		'process_time_limit'       => 1200,
		'video_width_ratio'        => 16,
		'video_height_ratio'       => 9
	],
	'Auth'   => [
		'enabled'                      => true,
		'type'                         => 'Form',
		'default_user_name'            => 'administrator',
		'default_user_display_name'    => 'システム管理者',
		'default_user_first_name'      => 'system',
		'default_user_family_name'     => 'administrator',
		'default_user_email'           => 'admin@localhost',
		'default_user_password'        => 'admin'
	],
	'Params' => [
		'default_version' => '9.5'
	],
	'Build'  => [
		'tasks' => [
			'encode' => [
				'background' => true,
				'hls'        => [
					'video_codec'       => 'libx264',
					'audio_codec'       => 'aac',
					'segment_time'      => 2,
					'segment_format'    => 'mpegts',
					'segment_file_name' => '%05d.ts'
				]
			],
			'deploy' => [
				'thumbnail' => [
					'size_ratio' => 0.5
				],
				'preview'   => [
					'size_ratio' => 0.25
				]
			]

		]
	]
];
