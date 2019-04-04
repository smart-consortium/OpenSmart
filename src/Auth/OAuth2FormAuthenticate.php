<?php

namespace App\Auth;

use Cake\Core\Configure;
use Cake\Http\Exception\InternalErrorException;
use Cake\Http\Response;
use Cake\Network\Request;

class OAuth2FormAuthenticate extends AbstractOAuth2Authenticate
{

	/**
	 * Checks the fields to ensure they are supplied.
	 *
	 * @param \Cake\Network\Request $request The request that contains login information.
	 * @param array                 $fields The fields to be checked.
	 * @return bool False if the fields have not been supplied. True if they exist.
	 */
	protected function _checkFields(Request $request, array $fields)
	{
		foreach ([$fields['username'], $fields['password']] as $field) {
			$value = $request->getData($field);
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
	 * @param Response              $response Unused response object.
	 * @return mixed False on login failure.  An array of User data on success.
	 */
	public function authenticate(Request $request, Response $response)
	{
		$fields = $this->_config['fields'];
		if (!$this->_checkFields($request, $fields)) {
			return false;
		}

		$cHandle = curl_init();
		$scope = Configure::read('Auth.options.scope');
		$results = $this->getTokenUsingUserCredential($cHandle,
		                                              $request->getData($fields['username']),
		                                              $request->getData($fields['password']), $scope, true);
		$ret = $this->checkStatusOk($results, $statusMean, $jsonDecodedBody);
		if ($ret === true) {
			$this->resetCurlHandle($cHandle);
			$attrs = $this->getUserAttrWithAccessToken($cHandle, $jsonDecodedBody['access_token']);
			curl_close($cHandle);
			$user = ['username'      => $attrs['jsonDecodedBody']['userid'],
			         'access_token'  => $jsonDecodedBody['access_token'],
			         'refresh_token' => $jsonDecodedBody['refresh_token'],
			         'expires_in'    => $jsonDecodedBody['expires_in']];
			return $user;
		} else {
			curl_close($cHandle);
			if ($statusMean === '3') {
				//ロケーション転送のケース
				throw new InternalErrorException();
				//TODO ERROR
			} elseif ($statusMean === '4') {
				//クライアント由来
				throw new InternalErrorException();
			} elseif ($statusMean === '5') {
				//サーバ起因エラー
				throw new InternalErrorException();
				//TODO ERROR
			} else {
				//TODO ERROR
				throw new InternalErrorException();
			}
			exit;
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
	}
}
