<?php
/**
 * @author    Magic Zhu <234079961@qq.com>
 * @version   0.1.0
 * @link      https://github.com/zhuzhengyu/magic_api
 */
// namespace magic_api;
class magic_core {
	public $use_file_cache = false;

	public $project_list;
	public $selected_project;

	public $file_list;
	public $selected_file;

	public $api_list;
	public $selected_api;

	public $interface;
// 	public $class_name;

	public $api_detail;
	public $api_request_uri;
	public $api_request_url;
	public $api_request_method;
	public $api_request_data;
	public $api_request_result;

	public function __construct($param = array()) {
		$this->customer_file_path = 'customer';
		self::projectList();
		self::reload_construct_param($param);
	}
	
	public function reload_construct_param($param = array()) {
		if (isset($param['p'])) {
			$this->selected_project = $param['p'];
			if ($this->selected_project) self::fileList();
		}
		
		if (isset($param['f'])) {
			$this->selected_file = $param['f'];
			if ($this->selected_file) self::apiList();
		}
		
		if (isset($param['m'])) {
			$this->selected_api = $param['m'];
			if ($this->selected_api) self::apiDetail();
		}
		
		$this->api_hash_key = md5(serialize($param));
	}

	/**
	 * @method 调试api接口
	 * @return unknown
	 */
	public function debug_api() {
		include_once 'vendor/restclient.php';
		$this->api_request_url = $this->TARGET_URL . '/' . trim($this->api_request_uri, '/');
		$this->api_request_data = http_build_query($_POST);
		$rc = new RestClient();
		$result = $rc->execute($this->api_request_url, $this->api_request_method, $this->api_request_data);
		self::collectDebugInfo();
// 		$this->api_hash_data[$this->api_hash_key] = '';
// 		file_put_contents('testing/logs/', $content, FILE_APPEND);
		$this->api_request_result = $result;
		$response = json_encode(json_decode($result->response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		return $response;
	}
	
	private function collectDebugInfo() {
		$content['request_url']		= $this->api_request_url;
		$content['request_method']	= $this->api_request_method;
		$content['request_data']	= $this->api_request_data;
		$content['response']		= $this->api_request_result;
		$key = $this->api_hash_key;
		$this->$key = $content;
// 		$this->api_hash_key = $content;
	}

	private function projectList() {
		$project_list = scandir($this->customer_file_path);
		foreach ($project_list as $k => $v) {
			$path = 'customer/' . $v;
			if (in_array($v, array('.', '..')) || !is_dir($path)) unset($project_list[$k]);
		}
		$this->project_list = $project_list;
	}

	private function fileList() {
		$config_json = file_get_contents($this->customer_file_path . '/' . $this->selected_project . '/config.json');
		$config = json_decode($config_json, true);
		foreach ($config as $k => $v) {
// 			defined($k) || define($k, $v);
			$this->$k = $v;
		}
		
		is_dir($this->customer_file_path . '/' . $this->selected_project . '/api') || mkdir($this->customer_file_path . '/' . $this->selected_project . '/api');
		$suf_file_arr = explode(";", $this->SUF_FILE);
		if ($this->use_file_cache == false) {
			$createFileList = function ($path, $customer_path) use (&$createFileList) {
				if (is_dir($path)) {
					$result = scandir($path);
					foreach ($result as $k => $v) {
						if (in_array($v, array('.', '..'))) continue;
						$sub_path = $path . '/' . $v;
						$sub_customer_path = $customer_path . '/' . $v;
						if (is_dir($sub_path)) {
							!is_dir($sub_customer_path) && mkdir($sub_customer_path);
							$createFileList($sub_path, $sub_customer_path);
						} else {
							!file_exists($sub_customer_path) && file_put_contents($sub_customer_path, '');
						}
					}
				} else {
					mkdir($path);
				}
			};
			$createFileList($this->BASEPATH, $this->customer_file_path . '/' . $this->selected_project . '/api');
		}
		
// 		scandir($this->customer_file_path . '/' . $this->selected_project . '/api');
		$getFileList = function ($path) use (&$getFileList) {
			if (is_dir($path)) {
				$result = scandir($path);
				foreach ($result as $k => $v) {
					if (in_array($v, array('.', '..'))) continue;
					$sub_path = $path . '/' . $v;
					if (is_dir($sub_path)) $getFileList($sub_path);
					else $this->file_list[] = $path . '/' . $v;
				}
			}
		};
		$key = $this->customer_file_path . '|' . $this->selected_project . '|api';//@TODO,临时解决重复遍历问题
		if (!isset($this->$key)) $getFileList($this->customer_file_path . '/' . $this->selected_project . '/api');
		$this->$key = true;
		
		foreach ($this->file_list as $k => $v) {
			$temp = explode(".", $v);
			if (!in_array(end($temp), $suf_file_arr)) unset($this->file_list[$k]);
			else $this->file_list[$k] = str_replace($this->customer_file_path . '/' . $this->selected_project . '/api/', '', $v);
		}
	}

	private function saveApiComment() {
		$file = file_get_contents($this->BASEPATH . '/' . $this->selected_file);
		$replace_return = '<br/>';//@TODO
		$temp = str_replace(array("\r\n", "\r", "\n", "\t"), array($replace_return, $replace_return, $replace_return, '    '), $file);//@TODO

		$temp_arr = explode($replace_return, $temp);

		$start_doc = false;
		$end_doc = false;
		$save_file_arr = array();
		foreach ($temp_arr as $k => $v) {
			$trim_v = trim($v);
			if ($trim_v == '/**') $start_doc = true;
			if ($trim_v == '*/') $end_doc = true;

			if ($start_doc != true) continue;
			$api_name_arr = explode('@apiName', $trim_v);
			if (isset($api_name_arr[1])) $apiName = trim(end($api_name_arr));
			$save_arr[] = $trim_v;
			if ($start_doc == true && $end_doc == true)	{
				if (!isset($apiName)) continue;
				$method_temp_arr = explode(' ', stristr($temp_arr[$k + 1], '(', true));
				$save_arr[] = end($method_temp_arr);
				$save_file_arr[$apiName] = $save_arr;
				$save_arr = array();
				$start_doc = $end_doc = false;
			}
		}

		$save_data = json_encode($save_file_arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		$file = 'customer/' . $this->PROJECT . '/api/' . $this->selected_file;
		file_put_contents($file, $save_data);
	}

	private function apiList() {
		$this->use_file_cache == false && self::saveApiComment();
		$file = 'customer/' . $this->PROJECT . '/api/' . $this->selected_file;
		$data_json = file_get_contents($file);
		$data_arr = json_decode($data_json, true);

		$api_list = array();
		if (is_array($data_arr)) {
			foreach ($data_arr as $apiName => $docComment) {
				$t = str_replace("\t", ' ', $docComment);
				if (isset($t[1])) {
					$tt = explode(' ', $t[1]);
					foreach ($tt as $k => $v) {
						if ($v == ' ') unset($tt[$k]);
					}
					$api_list[$apiName] = end($tt);
				}
			}
		}

		$this->api_list = $api_list;
	}

	private function apiDetail() {
		$file = 'customer/' . $this->PROJECT . '/api/' . $this->selected_file;
		$data_json = file_get_contents($file);
		$data_arr = json_decode($data_json, true);
		$a = $data_arr[$this->selected_api];
		$first_line_arr = explode(' ', str_replace(array('{', '}'), '', $a[1]));
		$this->api_request_method = $first_line_arr[2];//@TODO
		$this->api_request_uri = $first_line_arr[3];//@TODO
		foreach ($a as $k => $v) {
			if (stristr($v, '@apiSampleRequest')) {
				$request_uri = explode(' ', $v);
				$request_uri = end($request_uri);
				$this->api_request_uri = $request_uri;
			}
			if (!stristr($v, '@apiParam')) continue;
			$v = str_replace(array("\t", ']', '[', '*', '@apiParam'), array(' ', ' ', '', '', ''), $v);
			$row = explode(' ', $v);
			$new_row = array();
			// 		pr($v);
			foreach($row as $kk => $vv) {
				if ($vv != '') {
					if (isset($new_row[2])) {
						$new_row[2] .= $vv;
					} else {
						$new_row[] = $vv;
					}
				}
			}
			$save[$k]['type']	= $new_row[0];
			$save[$k]['param']	= $new_row[1];
			$save[$k]['desc']	= $new_row[2];
		}
		$this->api_detail = $save;
	}
}