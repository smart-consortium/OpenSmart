<?php

namespace App\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Core\Configure;

abstract class AbstractOAuth2Authenticate extends BaseAuthenticate
{
	/**
	 *
	 * getTokenUsingUserCredential
	 *
	 * GrantType==ResourceOwnerPassword認証の、認証・アクセストークン取得
	 *
	 * @param resource &$cHandle cURLハンドル
	 * @param string    $username
	 * @param string    $password
	 * @param string    $scope ''の時は未設定
	 * @param bool      $authorizeHeader true: Authorizationヘッダー指定、false: POSTのBody指定
	 * @return array 応答結果を配列にして返す
	 */
	protected function getTokenUsingUserCredential(&$cHandle, $username, $password, $scope, $authorizeHeader = true)
	{
		$url = Configure::read('Auth.options.token_url');

		curl_setopt($cHandle, CURLOPT_URL, $url);
		curl_setopt($cHandle, CURLOPT_HEADER, true);
		curl_setopt($cHandle, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($cHandle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);    //認証方法の指定 Basic

		//falseを設定して、cURLはサーバー証明書の検証を停止し、オレオレ証明書を認める。（本番はTRUEにすること)
		curl_setopt($cHandle, CURLOPT_SSL_VERIFYPEER, false);
		//curl_setopt($cHandle, CURLOPT_SSL_VERIFYHOST, 2);	//値2は、SSLピア証明書に一般名が存在するか、そして、その名前がホスト名と一致するかを検証します。本番では必ず2にすること。

		//認証サービスが、アプリケーションに発行したclient_idとclient_secretのセット
		$client_id = 'tektectestclient';
		$client_secret = 'tektectestpasswd';

		if ($authorizeHeader === true) {
			//client_idとclient_secretを、HTTP要求ヘッダーのAuthorizationで指定するタイプ
			$credentials = $client_id . ':' . $client_secret;
			$headers = [
				"Authorization: Basic " . base64_encode($credentials)
			];
			curl_setopt($cHandle, CURLOPT_HTTPHEADER, $headers);
		}

		//FORM関連
		curl_setopt($cHandle, CURLOPT_POST, true); //POST指定. "Content-Type: application/x-www-form-urlencoded"形式となる。
		$postFields = 'grant_type=password';    //grant_typeの指定
		$postFields .= '&username=' . urlencode($username);
		$postFields .= '&password=' . urlencode($password);
		if ($scope) {
			$postFields .= '&scope=' . urlencode($scope);
		}

		if ($authorizeHeader !== true) {
			//client_idとclient_secretを、POSTのBodyで指定するタイプ
			$postFields .= '&client_id=' . urlencode($client_id);
			$postFields .= '&client_secret=' . urlencode($client_secret);
		}
		curl_setopt($cHandle, CURLOPT_POSTFIELDS, $postFields);

		$response = curl_exec($cHandle);

		$results = [];

		//ステータスコード取得
		$results['statusCode'] = curl_getinfo($cHandle, CURLINFO_HTTP_CODE);

		//headerとbodyの取り出し
		$headerSize = curl_getinfo($cHandle, CURLINFO_HEADER_SIZE);
		$results['header'] = substr($response, 0, $headerSize);
		$results['body'] = substr($response, $headerSize);

		// json形式で返ってくるので、配列に変換
		$results['jsonDecodedBody'] = json_decode($results['body'], true);

		return $results;
	}

	/**
	 *
	 * validateAccessToken
	 *
	 * アクセストークン有効性のチェック
	 *
	 * @param resource &$cHandle cURLハンドル
	 * @param string    $accessToken アクセストークン
	 * @return array 応答結果を配列にして返す
	 */
	protected function validateAccessToken(&$cHandle, $accessToken)
	{
		$url = Configure::read('Auth.options.validate_url');

		//curl転送オプション指定
		curl_setopt($cHandle, CURLOPT_URL, $url);
		curl_setopt($cHandle, CURLOPT_HEADER, true);    //ヘッダーの内容を出力
		curl_setopt($cHandle, CURLOPT_RETURNTRANSFER, true);    //結果を文字列として取得
		//falseを設定して、cURLはサーバー証明書の検証を停止し、オレオレ証明書を認める。（本番はTRUEにすること)
		curl_setopt($cHandle, CURLOPT_SSL_VERIFYPEER, false);
		//値2は、SSLピア証明書に一般名が存在するか、そして、その名前がホスト名と一致するかを検証します。本番では必ず2にすること。
		//curl_setopt($cHandle, CURLOPT_SSL_VERIFYHOST, 2);

		//FORM関連
		//POST指定. "Content-Type: application/x-www-form-urlencoded"形式となる。
		curl_setopt($cHandle, CURLOPT_POST, true);
		$postFields = 'access_token=' . urlencode($accessToken);

		curl_setopt($cHandle, CURLOPT_POSTFIELDS, $postFields);

		$response = curl_exec($cHandle);

		$results = [];

		//ステータスコード取得
		$results['statusCode'] = curl_getinfo($cHandle, CURLINFO_HTTP_CODE);

		//headerとbodyの取り出し
		$headerSize = curl_getinfo($cHandle, CURLINFO_HEADER_SIZE);
		$results['header'] = substr($response, 0, $headerSize);
		$results['body'] = substr($response, $headerSize);

		// json形式で返ってくるので、配列に変換
		$results['jsonDecodedBody'] = json_decode($results['body'], true);

		return $results;
	}

	/**
	 *
	 * getTokenUsingRefreshToken
	 *
	 * refresh_tokenを使った、認証・アクセストークンの再取得
	 *
	 * @param resource &$cHandle cURLハンドル
	 * @param string    $refreshToken
	 * @param bool      $authorizeHeader true: Authorizationヘッダー指定、false: POSTのBody指定
	 * @return array 応答結果を配列にして返す
	 */
	protected function getTokenUsingRefreshToken(&$cHandle, $refreshToken, $authorizeHeader = true)
	{
		$url = Configure::read('Auth.options.refresh_url');

		//curl転送オプション指定
		curl_setopt($cHandle, CURLOPT_URL, $url);
		curl_setopt($cHandle, CURLOPT_HEADER, true);    //ヘッダーの内容を出力
		curl_setopt($cHandle, CURLOPT_RETURNTRANSFER, true);    //結果を文字列として取得

		//認証関連
		curl_setopt($cHandle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);    //認証方法の指定 Basic
		//falseを設定して、cURLはサーバー証明書の検証を停止し、オレオレ証明書を認める。（本番はTRUEにすること)
		curl_setopt($cHandle, CURLOPT_SSL_VERIFYPEER, false);
		//値2は、SSLピア証明書に一般名が存在するか、そして、その名前がホスト名と一致するかを検証します。本番では必ず2にすること。
		//curl_setopt($cHandle, CURLOPT_SSL_VERIFYHOST, 2);

		//認証サービスが、アプリケーションに発行したclient_idとclient_secretのセット
		$client_id = Configure::read('Auth.options.client_id');
		$client_secret = Configure::read('Auth.options.client_secret');

		if ($authorizeHeader === true) {
			//client_idとclient_secretを、HTTP要求ヘッダーのAuthorizationで指定するタイプ
			$credentials = $client_id . ':' . $client_secret;
			$headers = [
				"Authorization: Basic " . base64_encode($credentials)
			];
			curl_setopt($cHandle, CURLOPT_HTTPHEADER, $headers);
		}

		//FORM関連
		//POST指定. "Content-Type: application/x-www-form-urlencoded"形式となる。
		curl_setopt($cHandle, CURLOPT_POST, true);

		$postFields = 'grant_type=refresh_token';    //grant_typeの指定
		$postFields .= '&refresh_token=' . urlencode($refreshToken);

		if ($authorizeHeader !== true) {
			//client_idとclient_secretを、POSTのBodyで指定するタイプ
			//
			$postFields .= '&client_id=' . urlencode($client_id);
			$postFields .= '&client_secret=' . urlencode($client_secret);
		}
		curl_setopt($cHandle, CURLOPT_POSTFIELDS, $postFields);

		$response = curl_exec($cHandle);

		$results = [];

		//ステータスコード取得
		$results['statusCode'] = curl_getinfo($cHandle, CURLINFO_HTTP_CODE);

		//headerとbodyの取り出し
		$headerSize = curl_getinfo($cHandle, CURLINFO_HEADER_SIZE);
		$results['header'] = substr($response, 0, $headerSize);
		$results['body'] = substr($response, $headerSize);

		// json形式で返ってくるので、配列に変換
		$results['jsonDecodedBody'] = json_decode($results['body'], true);

		return $results;
	}

	/**
	 *
	 * checkStatusOk
	 * HTTP応答のステータスコードをチェックし、2xxなら、jsondecodeされた配列をセットして返す。
	 *
	 * @param array   $results 処理済みのHTTP応答配列
	 * @param string &$statusMean HTTP応答コードの先頭１文字（'1','2','3','4')。取得失敗なら''。
	 * @param array   $jsonDecodedBody return値がtrue(つまり、2xx）の時、HTTP応答BODYをjson_decodeした結果配列
	 * @return bool HTTP応答コードが2xxならtrue. それ以外はfalse
	 */
	protected function checkStatusOk($results, &$statusMean, &$jsonDecodedBody)
	{
		$ret = false;
		$jsonDecodedBody = [];
		$statusMean = substr($results['statusCode'], 0, 1);
		switch ($statusMean) {
			case '1':    //情報
				//printf("ERR: 情報コードはいまのところ想定外\n");
				break;
			case '2':    //成功
				$jsonDecodedBody = (!empty($results['jsonDecodedBody'])) ? $results['jsonDecodedBody'] : [];
				$ret = true;
				break;
			case '3':    //リダイレクト
				//printf("WARN: リダイレクトされた\n");
				break;
			case '4':    //クライアント起因エラー
				//printf("ERR: クライアント起因エラー発生\n");
				//OAuth2.0プロバイダーよりエラー詳細が格納されている可能性があるので、取り出しておく.
				$jsonDecodedBody = (!empty($results['jsonDecodedBody'])) ? $results['jsonDecodedBody'] : [];
				break;
			case '5':    //サーバ起因エラー
				//printf("ERR: サーバ起因エラー発生\n");
				//OAuth2.0プロバイダーよりエラー詳細が格納されている可能性があるので、取り出しておく.
				$jsonDecodedBody = (!empty($results['jsonDecodedBody'])) ? $results['jsonDecodedBody'] : [];
				break;
			default:
				//printf("ERR: 論理エラー\n");
				$statusMean = '';
		}
		return $ret;
	}

	/**
	 *resetCurlHandle
	 *
	 * セッションハンドル解放と再初期化. PHP5.3以下対策
	 * @param resource &$cHandle cURLハンドル
	 * @return void
	 */
	protected function resetCurlHandle(&$cHandle)
	{
		curl_close($cHandle);
		$cHandle = curl_init();
	}

	/**
	 *
	 * getUserAttrWithAccessToken
	 *
	 * アクセストークンをつかった会員の属性取得
	 *
	 * @param resource &$cHandle cURLハンドル
	 * @param string    $accessToken アクセストークン
	 * @return array 応答結果を配列にして返す
	 */
	protected function getUserAttrWithAccessToken(&$cHandle, $accessToken)
	{
		$url = Configure::read('Auth.options.attributes_url');    //自分の属性を取ってくる
		//$url = 'https://qaz.allcreator.net/tektec/v0.01/oauth2/user?id='.'A456789';	//他者IDの属性をもってくる。
		//（但し、これをできるのは固定ＩＰの管理サーバのみ
		// とはいっても、v0.01はi/f確認が目的なので、
		// IP固定でなくても動くようにわざとしてます。

		//curl転送オプション指定
		curl_setopt($cHandle, CURLOPT_URL, $url);
		curl_setopt($cHandle, CURLOPT_HEADER, true);    //ヘッダーの内容を出力
		curl_setopt($cHandle, CURLOPT_RETURNTRANSFER, true);    //結果を文字列として取得
		//falseを設定して、cURLはサーバー証明書の検証を停止し、オレオレ証明書を認める。（本番はTRUEにすること)
		curl_setopt($cHandle, CURLOPT_SSL_VERIFYPEER, false);
		//値2は、SSLピア証明書に一般名が存在するか、そして、その名前がホスト名と一致するかを検証します。本番では必ず2にすること。
		//curl_setopt($cHandle, CURLOPT_SSL_VERIFYHOST, 2);

		//トークンの指定（access_tokenを、HTTP要求ヘッダーのAuthorizationで指定するタイプ)
		$headers = [
			"Authorization: Bearer " . $accessToken
		];
		curl_setopt($cHandle, CURLOPT_HTTPHEADER, $headers);

		//GET
		curl_setopt($cHandle, CURLOPT_HTTPGET, true); //GET指定

		$response = curl_exec($cHandle);

		$results = [];

		//ステータスコード取得
		$results['statusCode'] = curl_getinfo($cHandle, CURLINFO_HTTP_CODE);

		//headerとbodyの取り出し
		$headerSize = curl_getinfo($cHandle, CURLINFO_HEADER_SIZE);
		$results['header'] = substr($response, 0, $headerSize);
		$results['body'] = substr($response, $headerSize);

		// json形式で返ってくるので、配列に変換
		$results['jsonDecodedBody'] = json_decode($results['body'], true);

		return $results;
	}
}
