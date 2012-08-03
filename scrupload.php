<?php

/*
 * 1. Download Scrup: https://github.com/rsms/scrup#download--install
 * 2. Put this file on your local web server.
 * 3. Set Receiver URL in Scrup (with your Copy login/password):
 *       http://localhost/scrupload.php?name={filename}&username=myemailaddress&password=mypassword
 * 4. Take screenshots and paste links!
*/

function rest_call($method, $url, $params, $auth_token = '') {
	$ctx = array(
		'http' => array(
			'method' => $method,
			'header' => array_merge(array(
				'X-Client-Version: 1.0.00',
				'X-Client-Type: API',
				'X-Api-Version: 1.0',
			), ($auth_token ? array('X-Authorization: '. $auth_token) : array())),
			'content' => !is_array($params) ? $params : join(array_map(function($value, $key) {
				return $key .'='. $value;
			}, $params, array_keys($params)), '&'),
		)
	);
	return @file_get_contents(REST_ENDPOINT. $url, false, stream_context_create($ctx)) ?: false;
}

define('REST_ENDPOINT', 'http://api.copy.com');
define('USERNAME', $_GET['username']);
define('PASSWORD', $_GET['password']);
define('FILENAME', $_GET['name']);

if (!USERNAME || !PASSWORD || !FILENAME) { echo "Missing stuff.\n"; exit; }
$filedata = file_get_contents('php://input');

// build path to where we'll upload and link
$filepath = '/scruploads/'. FILENAME;

// log in
$auth_info = rest_call('POST', '/rest/session', array(
	'email' => USERNAME,
	'password' => PASSWORD,
));
$auth_info = json_decode($auth_info);

// auth failed?
if (!isset($auth_info->auth_token)) return false;

// upload object
$objects_info = rest_call('POST', '/rest/objects'. $filepath, $filedata, $auth_info->auth_token);

// create link
$link_info = rest_call('POST', '/rest/links', array(
	'path' => $filepath,
), $auth_info->auth_token);
$link_info = json_decode($link_info);

// return link
header('HTTP/1.1 201 Created');
header('Content-Type: text/plain; charset=utf-8');
header('Content-Length: '.strlen($link_info->url_short));
echo $link_info->url_short;

// useful if on Mountain Lion and have the terminal-notifier gem installed
// shell_exec("terminal-notifier -title 'Scruploaded!' -message '{$short}'");

?>