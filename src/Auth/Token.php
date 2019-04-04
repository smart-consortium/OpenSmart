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
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Auth;

use App\Utility\StringUtility;
use Cake\Core\Configure;

/**
 * 認証トークン文字列操作クラス
 */
class Token
{
	/*
	 * URLに認証情報を設定する。
	 * 認証には２パターンがあり、APIの呼び出しには認証局から取得したaccess_tokenを利用する。
	 * ビデオリソース取得時には、apacheのmod_auth_token用のトークンを利用する。
	 *
	 * $url : トークンを設定したいURL。相対／絶対URLのどちらでもよい。
	 * $actoken : api呼び出し時に設定するaccess_token文字列。ビデオリソース取得用のURLが渡された場合は、この引数は無視される。
	 * @return 認証情報が付与されたURLを返す。ただし、以下の場合は渡されたURLを絶対URLに変換しただけのものを返す。
	 *        ・API呼び出し用のURLが指定されたが、$actokenが指定されていない場合
	 *        ・API／ビデオリソースのどちらの形式でもないURLが指定された場合
	 */
	public static function with($url, $actoken = null)
	{
		if (StringUtility::is_empty($url)) {
			return null;
		}
		//絶対URL形式である。そうでなければとにかく絶対URLに変換
		if (StringUtility::is_url($url) === false) {
			$url = StringUtility::create_url($url);
		}
		if (strpos($url, "/video/")) {
			$exclude = Token::without_auth_token($url);
			//access_tokenでなく、mod_auth_token用の文字列を付加する。
			//tokenが付いている場合は、一度それを外す
			//~.ism/の形式が含まれている場合は、それ以降の文字列を一時取り払ってから
			//トークンを付与後に、それ以降のURLを復元してから返す。
			if (Token::is_ism_url($exclude)) {
				//.ism以降を除く
				$video_paths = explode(".ism", $exclude, 2);
				$ism = $video_paths[0] . ".ism";
				$before = Token::set_auth_token($ism);
				$after = $video_paths[1];
				return $before . $after;
			} else {
				//通常のトークン設定
				return Token::set_auth_token($exclude);
			}
		}
		return $url;
	}

	public static function set_auth_token($url)
	{
		$video_paths = explode("/video/", $url, 2);
		$protected = $video_paths[0] . "/video/";
		$resource = "/" . $video_paths[1];
		$hexTime = dechex(time());
		$token = md5(Configure::read('Auth.mod_auth_token_client_secret') . $resource . $hexTime);
		return $protected . $token . "/" . $hexTime . $resource;
	}

	public static function reset_access_token($url, $token)
	{
		$exclude = Token::exclude_access_token($url);
		if (StringUtility::has_query($exclude)) {
			$paths = explode("?", $exclude, 2);
			return $paths[0] . "?access_token=" . $token . "&" . $paths[1];
		} else {
			return $exclude . "?access_token=" . $token;
		}
	}

	public static function exclude_access_token($url)
	{
		//クエリがなければ処理は不要
		if (!StringUtility::has_query($url)) {
			return $url;
		} elseif (Token::has_access_token($url)) {
			$paths = explode("?", $url, 2);
			$base = $paths[0];
			$query = $paths[1];
			$queries = explode("&", $query);
			$excluded = "";
			foreach ($queries as $q) {
				if (preg_match("/^access_token=/", $q)) {
					continue;
				} else {
					if (!StringUtility::is_empty($excluded)) {
						$excluded .= "&";
					}
					$excluded .= $q;
				}
			}
			return $base . "?" . $excluded;
		} else {
			return $url;
		}
	}

	// URLに含まれる/videos/の後にトークン文字列が来る。
	// /videos/ -- token -- / -- token -- / -- user_id -- / -- year --/ -- day --/
	// ・tokenは / でわけられた２区画分になる。
	// ・tokenのあとには、ユーザーID文字列がくる
	// ・ユーザーIDの次は年を表す数字である
	//
	// トークンがあるかどうかの判定には、以下のルールを使う
	// ・ /videos/ の 2つ右となりにあるのが4桁の数字ならば、トークンは含まれない
	public static function without_auth_token($url)
	{
		if (strpos($url, "/video/") !== false) {
			$new_url = "";
			$paths = explode(DIRECTORY_SEPARATOR, $url);
			for ($i = 0; $i < count($paths); $i++) {
				$p = $paths[$i];
				if ($new_url != "") {
					$new_url .= "/";
				}
				$new_url .= $p;
				if ($p == "video") {
					$check = $paths[$i + 2];
					// videosの2つとなりに4桁数字があればトークンは含まれないので飛ばさない
					if (preg_match("/^\d{4}$/", $check) == 0) {
						// auth_token分を読み飛ばす
						$i += 2;
					}
				}
			}
			$url = $new_url;
		}
		return $url;
	}

	public static function is_ism_url($url)
	{
		return preg_match("/\.ism/", $url) != false;
	}

	public static function has_access_token($url)
	{
		return strpos($url, "access_token") != false;
	}

	public static function has_auth_token($url)
	{
		return strpos($url, "access_token") != false;
	}
}
