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

namespace App\Utility;


use App\Exception\FileNotFoundException;
use App\Utility\FileSystem\File;
use App\Utility\FileSystem\Folder;

class HlsUtility
{

	/**
	 * Make HLS manifest (main.m3u8) file
	 *
	 * @param string $output_dir Output directory path
	 * @param array  $options Bit rate options
	 */
	public static function make_manifest(string $output_dir, array $options): void
	{
		$file = new File($output_dir . HLS_MAIN_FILE, true, 0755);

		$file->append(HLS_MANIFEST_TYPE);
		$file->append(HLS_EXT_X_VERSION . ':' . HLS_EXT_X_VERSION_NUMBER);
		$file->append(HLS_COPYRIGHT_HEADER);

		foreach ($options as $rate) {
			$vr = $rate[PARAMS_TAG_BIT_RATE_VIDEO];
			if (self::_is_number($vr)) {
				if (self::_is_kilo_bite($vr)) {
					$band_width = $vr * 1024;
				} else {
					$band_width = $vr;
				}
				$stream_info = self::_stream_info($band_width);
				$file->append($stream_info);
				$file->append($vr . DS . HLS_SUB_FILE . '?bit_rate=' . $vr);
			}

		}
		$file->append(HLS_EXT_X_ENDLIST);
		$file->close();
	}

	/**
	 * @param string $src_path
	 * @param string $dest_path
	 * @return File
	 * @throws FileNotFoundException
	 */
	public static function copy_playlist_header(string $src_path, string $dest_path): File
	{
		$dest = new File($dest_path, true);
		$src = HlsUtility::find_playlist($src_path);
		$src_file = fopen($src, 'r');
		if ($src_file) {
			while ($line = fgets($src_file)) {
				if (StringUtility::startsWith($line, HLS_EXT_INF)) {
					break;
				}
				if (empty($line)) {
					continue;
				}
				$dest->append(trim($line));
			}
		}
		return $dest;
	}

	/**
	 * Find m3u8 playlist in argument directory
	 * @param string $dir Find target directory
	 * @return string m3u8 file path
	 * @throws FileNotFoundException
	 */
	public static function find_playlist(string $dir): string
	{
		if (empty($dir) || !file_exists($dir)) {
			throw new FileNotFoundException($dir);
		}
		$folder = new Folder($dir);
		return $folder->path . DS . $folder->find('.+.m3u8')[0];
	}

	/**
	 * @param $vr
	 * @return bool
	 */
	private static function _is_number($vr): bool
	{
		return preg_match('/^[1-9]\d*/', $vr, $result) == 1;
	}

	/**
	 * @param $vr
	 * @return bool
	 */
	private static function _is_kilo_bite($vr): bool
	{
		return preg_match('/[kK]?$/', $vr) == 1;
	}

	/**
	 * @param $band_width
	 * @return string
	 */
	private static function _stream_info($band_width): string
	{
		return '#EXT-X-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=' . $band_width;
	}
}