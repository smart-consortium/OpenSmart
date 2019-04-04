<?php

namespace App\Auth;

use Cake\Http\Exception\InternalErrorException;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Http\Response;
use Cake\I18n\Time;
use Cake\Network\Request;
use Cake\ORM\Locator\TableLocator;

class OAuth2TokenAuthenticate extends AbstractOAuth2Authenticate
{

	/**
	 * Checks the fields to ensure they are supplied.
	 *
	 * @param Request $request The request that contains login information.
	 * @return bool False if the fields have not been supplied. True if they exist.
	 */
	protected function _checkFields(Request $request)
	{
		$value = $request->getHeader('Authorized');
		//authorizedヘッダをチェックしてなければqueryをチェック
		if (empty($value) || !is_string($value)) {
			$value = $request->getSession()
			                 ->read(SESSION_ACCESS_TOKEN);
			if (empty($value) || !is_string($value)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Authenticates the identity contained in a request. Will use the `config.userModel`, and `config.fields`
	 * to find POST data that is used to find a matching record in the `config.userModel`. Will return false if
	 * there is no post data, either username or password is missing, or if the scope conditions have not been met.
	 *
	 * @param \Cake\Network\Request $request The request that contains login information.
	 * @param Response              $response
	 * @return mixed False on login failure.  An array of User data on success.
	 * @throws UnauthorizedException
	 */
	public function authenticate(Request $request, Response $response)
	{
		if (!$this->_checkFields($request)) {
			return false;
		}
		$access_token = $request->getSession()
		                        ->read(SESSION_ACCESS_TOKEN);
		if (empty($access_token) || !is_string($access_token)) {
			return false;
		}

		// dbに問い合わせしてaccess_tokenを保持しているかチェック
		// なければ認証サーバーに確認後、validであったら登録。
		// それでだめなら401
		$locator = new TableLocator();
		$table = $locator->get('tokens');
		$tokens = $table->find('all')
		                ->where(["tokens.access_token" => $access_token])
		                ->limit(1);
		$token = $tokens->first();
		if ($token) {
			//access_tokenの最終チェック時間が AUTH_CHECK_TIME_LIMIT 分以内ならばOK
			$last = new Time($token["checked"]);
			if ($last->wasWithinLast("5 minute")) {
				$user = ['username'      => $token['name'],
				         'access_token'  => $token["access_token"],
				         'refresh_token' => '',
				         'expires_in'    => ''];
				return $user;
			} else {
				$cHandle = curl_init();
				$results = $this->validateAccessToken($cHandle, $access_token);
				$ret = $this->checkStatusOk($results, $statusMean, $jsonDecodedBody);
				curl_close($cHandle);
				if ($ret === true) { //成功
					$token->set('checked', new Time());
					if ($table->save($token)) {
						$user = ['username'      => $token['name'],
						         'access_token'  => $access_token,
						         'refresh_token' => '',
						         'expires_in'    => ''];
						return $user;
					} else {
						//TODO DB保存エラー
						throw new InternalErrorException();
					}
				} else {
					if ($statusMean === '3') {
						//ロケーション転送のケース
						//SmartLog::log(LOG_ERR, "oauth error. 3");
					} elseif ($statusMean === '4') {
						//クライアント由来
						//SmartLog::log(LOG_ERR, "oauth error. 4");
					} elseif ($statusMean === '5') {
						//サーバ起因エラー
						//SmartLog::log(LOG_ERR, "oauth error. 5");
					} else {
						//SmartLog::log(LOG_INFO, "oauth error. unknown");
						throw new UnauthorizedException("invalid access token.");
					}
					//exit;
				}
			}
			return false;
		} else {
			$cHandle = curl_init();
			$results = $this->validateAccessToken($cHandle, $access_token);
			$ret = $this->checkStatusOk($results, $statusMean, $jsonDecodedBody);
			if ($ret === true) { //成功
				$this->resetCurlHandle($cHandle);
				$attrs = $this->getUserAttrWithAccessToken($cHandle, $access_token);
				curl_close($cHandle);
				$entity = $table->newEntity();
				$entity->set('name', $attrs['jsonDecodedBody']['userid']);
				$entity->set('access_token', $access_token);
				if ($table->save($entity)) {
					$user = ['username'      => $attrs['jsonDecodedBody']['userid'],
					         'access_token'  => $access_token,
					         'refresh_token' => '',
					         'expires_in'    => ''];
					return $user;
				} else {
					//TODO DB保存エラー
					throw new InternalErrorException();
				}
			} else {
				curl_close($cHandle);
				if ($statusMean === '3') {
					//ロケーション転送のケース
					//SmartLog::log(LOG_ERR, "oauth error. 3");
				} elseif ($statusMean === '4') {
					//クライアント由来
					//SmartLog::log(LOG_ERR, "oauth error. 4");
				} elseif ($statusMean === '5') {
					//サーバ起因エラー
					//SmartLog::log(LOG_ERR, "oauth error. 5");
				} else {
					//SmartLog::log(LOG_INFO, "oauth error. unknown");
					throw new UnauthorizedException("invalid access token.");
				}
				//exit;
			}
			return false;
		}
	}

	public function implementedEvents()
	{
		return [
			'Auth.afterIdentify' => 'afterIdentify',
		];
	}

	public function afterIdentify()
	{
		//ログイン後処理
	}
}
