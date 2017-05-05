<?php

$url = "http://ara-hair.com";
$path = array(
	"index.php",
	"index.html",
	"home.html"
);

$urls = packUrls($url, $path);
$res = async_get_url($urls);

print_r(array_column($res, "http_code", "url"));
exit;

function packUrls($url, $path) {
	$urls = array();

	foreach ($path as $p) {
		$urls[] = "{$url}/{$p}";
	}

	return $urls;
}

function async_get_url($url_array) {
	if (!is_array($url_array)) return false;

	$data    = array();
	$handle  = array();
	$running = 0;

	$mh = curl_multi_init();

	$i = 0;
	foreach($url_array as $url) {
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.57 Safari/537.36',
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
			'Connection: keep-alive',
		));
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_NOBODY, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

		curl_multi_add_handle($mh, $ch);

		$handle[$i++] = $ch;
	}

	do {
		curl_multi_exec($mh, $running);
		curl_multi_select($mh);
	} while ($running > 0);

	foreach($handle as $i => $ch) {
		$data[$i] = array(
			'url' => $url_array[$i],
			'errno' => -1,
			'http_code' => -1,
			'content' => '',
		);

		$errno = curl_errno($ch);
		if ($errno === 0) {
			$data[$i]['errno'] = $errno;

			$data[$i]['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($data[$i]['http_code'] === 200) {
				$data[$i]['content'] = curl_multi_getcontent($ch);
			}
		}
	}

	foreach($handle as $ch) {
		curl_multi_remove_handle($mh, $ch);
	}

	curl_multi_close($mh);

	return $data;
}

?>
