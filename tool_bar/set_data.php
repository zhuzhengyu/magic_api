<?php
try {
	if (!isset($_GET['data'])) throw new \Exception('no get data');
	$data = $_GET['data'];
	$data_arr = explode('|', $data);
	if (!isset($data_arr[1])) throw new \Exception('data error');
	$key = $data_arr[0];
	$value = $data_arr[1];
	if (!setcookie($key, $value)) throw new \Exception('set_data error 3');
} catch (\Exception $e) {
	pr($e->getMessage());//@TODO
}
// pr($_COOKIE);





function pr($var) {
	echo '<pre>';
	print_r($var);
	echo '</pre>';
}