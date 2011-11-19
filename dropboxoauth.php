<?php

/*
 * Dropbox OAuth PHP Client
 * https://github.com/gregschoen/dropboxoauth
 */

require_once('OAuth.php'); // from http://oauth.net

class DropboxOAuth
{
	public $http_code;
	public $url;
	public $host = "https://api.dropbox.com/1/";
	public $timeout = 10;
	public $connecttimeout = 10;
	public $ssl_verifypeer = FALSE;
	public $format = 'json';
	public $decode_json = TRUE;
	public $http_info;
	public $useragent = 'DropboxOAuth v0.1.0';

	function accessTokenURL()
	{
		return 'https://api.dropbox.com/1/oauth/access_token';
	}

	function authorizeURL()
	{
		return 'https://www.dropbox.com/1/oauth/authorize';
	}

	function requestTokenURL()
	{
		return 'https://api.dropbox.com/1/oauth/request_token';
	}

	function lastStatusCode()
	{
		return $this->http_status;
	}

	function lastAPICall()
	{
		return $this->last_api_call;
	}

	function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL)
	{
		$this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
		$this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
		if (!empty($oauth_token) && !empty($oauth_token_secret))
		{
			$this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
		}
		else
		{
			$this->token = NULL;
		}
	}

	function getRequestToken($oauth_callback = NULL)
	{
		$parameters = array();
		if (!empty($oauth_callback))
		{
			$this->oauth_callback = $oauth_callback;
		}
		$request = $this->oAuthRequest($this->requestTokenURL(), 'GET', $parameters);
		$token = OAuthUtil::parse_parameters($request);
		$this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
		return $token;
	}

	function getAuthorizeURL($token)
	{
		if (is_array($token))
		{
			$token = $token['oauth_token'];
		}
		return $this->authorizeURL() . "?oauth_token={$token}" . (isset($this->oauth_callback)) ? "&oauth_callback={$this->oauth_callback}" : "";
	}

	// dropbox does not use the authenticate method
	// function getAuthenticateURL($token)
	
	// also appears to not use the verifier string
	function getAccessToken($oauth_verifier = FALSE)
	{
		$parameters = array();
		if (!empty($oauth_verifier))
		{
			$parameters['oauth_verifier'] = $oauth_verifier;
		}
		$request = $this->oAuthRequest($this->accessTokenURL(), 'GET', $parameters);
		$token = OAuthUtil::parse_parameters($request);
		$this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
		return $token;
	}

	function get($url, $parameters = array())
	{
		$response = $this->oAuthRequest($url, 'GET', $parameters);
		if ($this->format === 'json' && $this->decode_json)
		{
			return json_decode($response);
		}
		return $response;
	}

	// get response without json decoding - specifically to get files
	function pull($url, $parameters = array())
	{
		return $this->oAuthRequest($url, 'GET', $parameters);
	}
	
	function put($url, $filename)
	{
		$response = $this->oAuthRequest($url, 'PUT', array(), $filename);
		if ($this->format === 'json' && $this->decode_json)
		{
			return json_decode($response);
		}
		return $response;
	}

	function post($url, $parameters = array())
	{
		$response = $this->oAuthRequest($url, 'POST', $parameters);
		if ($this->format === 'json' && $this->decode_json)
		{
			return json_decode($response);
		}
		return $response;
	}

	function delete($url, $parameters = array())
	{
		$response = $this->oAuthRequest($url, 'DELETE', $parameters);
		if ($this->format === 'json' && $this->decode_json)
		{
			return json_decode($response);
		}
		return $response;
	}

	function oAuthRequest($url, $method, $parameters, $filename = NULL)
	{
		if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0)
		{
			$url = "{$this->host}{$url}.{$this->format}";
		}
		
		$request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
		$request->sign_request($this->sha1_method, $this->consumer, $this->token);
		switch ($method)
		{
			case 'GET':
				return $this->http($request->to_url(), 'GET');
			case 'PUT':
				return $this->http($request->to_url(), 'PUT', NULL, $filename);
			default:
				return $this->http($request->get_normalized_http_url(), $method, $request->to_postdata());
		}
	}

	function http($url, $method, $postfields = NULL, $filename = NULL)
	{
		$this->http_info = array();
		$ci = curl_init();
		/* Curl settings */
		curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
		curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
		curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
		curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
		curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
		curl_setopt($ci, CURLOPT_HEADER, FALSE);

		switch ($method)
		{
			case 'POST':
				curl_setopt($ci, CURLOPT_POST, TRUE);
				if (!empty($postfields))
				{
					curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
				}
				break;
			case 'DELETE':
				curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
				if (!empty($postfields))
				{
					$url = "{$url}?{$postfields}";
				}
				break;
			case 'PUT':
				$fp = fopen($filename, "r");
				curl_setopt($ci, CURLOPT_PUT, 1);
				curl_setopt($ci, CURLOPT_INFILE, $fp);
				curl_setopt($ci, CURLOPT_INFILESIZE, filesize($filename));
				break;
		}

		curl_setopt($ci, CURLOPT_URL, $url);
		$response = curl_exec($ci);
		$this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
		$this->http_info = array_merge($this->http_info, curl_getinfo($ci));
		$this->url = $url;
		curl_close($ci);
		return $response;
	}

	function getHeader($ch, $header)
	{
		$i = strpos($header, ':');
		if (!empty($i))
		{
			$key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
			$value = trim(substr($header, $i + 2));
			$this->http_header[$key] = $value;
		}
		return strlen($header);
	}
	
}

