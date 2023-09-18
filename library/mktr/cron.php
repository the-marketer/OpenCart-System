<?php
$dir = __DIR__ . '/';

define('MKTR_ROOT', $dir);
define('MKTR_LIB', $dir);

if (is_file($dir . 'Helper/Array2XML.php')) {
	require_once($dir . 'Helper/FileSystem.php');
	require_once($dir . 'Helper/Array2XML.php');
	require_once($dir . 'Helper/Data.php');
	require_once($dir . 'Helper/Valid.php');
}
$data = \Mktr\Helper\Data::init();
$store = $data->store;
$status = false;

if ($store === null) { $store = array(); }

\Mktr\Helper\Array2XML::setCDataValues(array('name', 'description', 'category', 'brand', 'size', 'color', 'hierarchy'));
\Mktr\Helper\Array2XML::$noNull = true;

foreach ($store as $k => $s) {
	$out = run($s);
	if ($out[1]) {
		$status = true;
		$store[$k] = $out[0];
	}
}

function getOnePage($page, $store, $list) {
	$end = $page * $store['limit'];
	$start = $end - $store['limit'];
	while ($start<=$end) {
		// echo $page.'-'.$start.PHP_EOL;
		$url = $store['link'] . 'mktr/api/feed' . ($store['q'] ? '&' : '?') . 'key=' . $store['rest_key'] . '&page=' . $start . '&limit=1&mime-type=json&no_save=1&t=' . time();
		$content = @file_get_contents($url);
		
		if ($content !== false) {
			$xmlArray = json_decode($content, true);
		} else {
			$xmlArray = [];
		}

		if (isset($xmlArray['products']['product'])) {
			foreach ($xmlArray['products']['product'] as $k => $p) {
				$list[] = $p;
			}
			if (empty($xmlArray['products']['product'])) {
				$start = $end;
			}
		}
		$start++;
		sleep(1);
	}
	return $list;
}

function run($store) {
	$time = time();
	$status = false;
	if ($store['cron_feed'] == 1 && $store['update_feed_time'] < $time) {
		$run = true;
		$list = array();
		$page = $store['page'];
		while ($run) {
			// echo $page.PHP_EOL;
			// $store['limit'] = 2;
			$url = $store['link'] . 'mktr/api/feed' . ($store['q'] ? '&' : '?') . 'key=' . $store['rest_key'] . '&page=' . $page . '&limit=' . $store['limit'] . '&mime-type=json&no_save=1&t=' . time();
			$content = @file_get_contents($url);

			if (empty($content)) {
				$list = getOnePage($page, $store, $list);
				// sleep(1);
				// $content = file_get_contents($url);
			} else {
				// $content = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
				// $xmlArray = json_decode(json_encode($content), true);
				// if (isset($xmlArray['product'])) {
				//	foreach ($xmlArray['product'] as $k=>$p) {
				$xmlArray = json_decode($content, true);
				if (isset($xmlArray['products']['product'])) {
					foreach ($xmlArray['products']['product'] as $k => $p) {
						// $p['url'] = str_replace('&amp;', '&', $p['url']);
						$list[] = $p;
					}
					// if (empty($xmlArray['product'])) {
					if (empty($xmlArray['products']['product'])) {
						$run = false;
					}
				} else {
					$run = false;
				}
			}
			$page++;
			sleep(1);
		}

		// \Mktr\Helper\FileSystem::writeFile('end.rd', $content);
		\Mktr\Helper\FileSystem::writeFile('products.' . $store['store_id'] . '.xml', \Mktr\Helper\Array2XML::cXML("products", array("product" => $list))->saveXML());
		$store['update_feed_time'] = strtotime("+" . $store['update_feed'] . " hour");
		$store['stop_page'] = $page;
		$status = true;
	}

	if ($store['cron_review'] == 1 && $store['update_review_time'] < time()) {
		file_get_contents($store['link'] . "mktr/api/Reviews" . ($store['q'] ? '&' : '?') . 'key=' . $store['rest_key'] . "&start_date=" . strtotime("-" . ($store['update_review']+1) . " hour"));
		$store['update_review_time'] = strtotime("+" . $store['update_review'] . " hour");
		$status = true;
	}
	return array($store, $status);
}

if ($status) {
	$data = new \Mktr\Helper\Data();
	$data->store = $store;
	$data->save();
}

echo json_encode($data->store) . PHP_EOL;
