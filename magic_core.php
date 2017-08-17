<?php
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

	public function __construct() {
		$this->customer_file_path = 'customer';
		self::projectList();
		if (isset($_GET['p'])) {
			$this->selected_project = $_GET['p'];
			if ($this->selected_project) self::fileList();
		}

		if (isset($_GET['f'])) {
			$this->selected_file = $_GET['f'];
			if ($this->selected_file) self::apiList();
		}

		if (isset($_GET['m'])) {
			$this->selected_api = $_GET['m'];
			if ($this->selected_api) self::apiDetail();
		}

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
			define($k, $v);
		}
		$this->file_list = scandir(BASEPATH);
		foreach ($this->file_list as $k => $v) {
			if (in_array($v, array('.', '..'))) unset($this->file_list[$k]);
		}
	}

	private function saveApiComment() {
		$file = file_get_contents(BASEPATH . '/' . $this->selected_file);
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
				$method_temp_arr = explode(' ', stristr($temp_arr[$k + 1], '(', true));
				$save_arr[] = end($method_temp_arr);
				$save_file_arr[$apiName] = $save_arr;
				$save_arr = array();
				$start_doc = $end_doc = false;
			}
		}

		$save_data = json_encode($save_file_arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		$file = 'customer/' . PROJECT . '/api/' . $this->selected_file;
		file_put_contents($file, $save_data);
	}

	private function apiList() {
		$this->use_file_cache == false && self::saveApiComment();
		$file = 'customer/' . PROJECT . '/api/' . $this->selected_file;
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
		$file = 'customer/' . PROJECT . '/api/' . $this->selected_file;
		$data_json = file_get_contents($file);
		$data_arr = json_decode($data_json, true);
		$a = $data_arr[$this->selected_api];
		$first_line_arr = explode(' ', str_replace(array('{', '}'), '', $a[1]));
		$method = $first_line_arr[3];
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