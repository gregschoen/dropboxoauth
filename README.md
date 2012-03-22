Dropbox OAuth Client for PHP
----------------------------

A modification of Abraham's [TwitterOAuth](https://github.com/abraham/twitteroauth) library to work with Dropbox.

Slight fixes to abstract away the differences between the two site's
implementation of the OAuth specifications. If you know how TwitterOAuth works, 
you'll have no problems implementing Dropbox with this one.

v0.2.0
- Updated to use API version 1
- Files Put is now supported through `->put()` call

v0.1.0
- Base functionality only, you can get files, not push files, yet.

Quick Links
-----------

Dropbox Apps Section
[https://www.dropbox.com/developers/apps](https://www.dropbox.com/developers/apps)

API Documentation
[http://www.dropbox.com/developers/reference/api](http://www.dropbox.com/developers/reference/api)

TwitterOAuth
[https://github.com/abraham/twitteroauth](https://github.com/abraham/twitteroauth)

TwitterOAuth Documentation
[https://github.com/abraham/twitteroauth/blob/master/DOCUMENTATION](https://github.com/abraham/twitteroauth/blob/master/DOCUMENTATION)

Usage
-----

Get Request Token and Redirect
```
$oauth = new DropboxOAuth($consumer_key,$consumer_secret);
$request = $oauth->getRequestToken($callback_url);
$url = $oauth->getAuthorizeURL($request);
```

Get Access Token
```
$oauth = new DropboxOAuth($consumer_key,$consumer_secret,$request['oauth_token'],$request['oauth_token_secret']);
$token = $oauth->getAccessToken();
```

Using the API
```
$oauth = new DropboxOAuth($consumer_key,$consumer_secret,$user_key,$user_secret);
$account = $oauth->get("https://api.dropbox.com/1/account/info");
$metadata = $oauth->get("https://api.dropbox.com/1/metadata/dropbox/");
$file = $oauth->pull("https://api-content.dropbox.com/1/files/dropbox/my-test-file.txt");
$upload = $oauth->put("https://api-content.dropbox.com/1/files_put/dropbox/test.txt","/path/to/file.txt");
```

Please note that `->pull()` is used to `->get()` without doing the automatic json_decode. 