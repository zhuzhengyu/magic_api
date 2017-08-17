<?php
require 'function.php';
require 'config.php';
class magic_api {
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

$mApi = new magic_api();

$project_list = $mApi->project_list;
$file_list = $mApi->file_list;
$api_list = $mApi->api_list;
$api_detail = $mApi->api_detail;

$url = 'api.php';
$this_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

?>


<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?php echo $title;?></title>
<script>
var site_url = '<?php echo URL;?>';
var css_site = '<?php echo CSS_SITE;?>';
// function group_list() {
// 	var url = site_url + '/site/group_list.php';
// 	$.get(url, {}, function(response) {
// 		$('#left').html(response);
// 	});
// }
</script>
<script src="<?php echo CSS_SITE;?>/js/jquery-3.2.1.js"></script>
<link rel="stylesheet" href="<?php echo CSS_SITE;?>/css/base.css" media="all" />
</head>
<body>
	<div id="main">
		<div id="left">
---------------项目---------------
			<div id="left_top">
				<table>
					<tr>
						<th>项目名</th>
						<th>操作</th>
					</tr>
<?php
if ($project_list) {
	foreach ($project_list as $k => $v) {
?>
					<tr>
						<td><a href="<?php echo $url . '?p=' . $v;?>"><?php echo $v;?></a></td>
						<td>
							<a href="<?php echo 'index.php?a=edit&p=' . $v;?>">编辑</a>
							<a href="<?php echo 'index.php?a=delete&p=' . $v;?>">删除</a>
						</td>
					</tr>
<?php
	}
}
?>			
				</table>
			</div>
---------------类文件---------------
			<div id="left_main">
				<table>
<?php
if (isset($file_list) && is_array($file_list)) {
	foreach ($file_list as $k => $v) {
?>
					<tr>
						<td><a href="<?php echo $url . '?p=' . $mApi->selected_project . '&f=' . $v;?>"><?php echo $v;?></a></td>
					</tr>
<?php
	}
}
?>
				</table>
			</div>
---------------方法---------------
			<div id="left_bottom">
				<table>
<?php
if (isset($api_list) && is_array($api_list)) {
	foreach ($api_list as $k => $v) {
?>
					<tr>
						<td><a href="<?php echo $url . '?p=' . $mApi->selected_project . '&f=' . $mApi->selected_file . '&m=' . $k;?>"><?php echo $v;?></a></td>
					</tr>
<?php
	}
}
?>
				</table>
			</div>
		</div>
		<div id="middle">
			<div id="middle_top">
			</div>
----------接口详情----------
			<div id="middle_main">
				<form action="<?php echo $this_url;?>" method="post">
				<table>
					<tr>
						<th>参数:</th>
						<th>填写参数值:</th>
						<th>参数类型</th>
						<th>描述</th>
					</tr>
<?php
if (isset($api_detail)) {
	foreach($api_detail as $k => $v) {?>
					<tr>
						<td><?php echo $v['param'];?></td>
						<td><input type="text" class="<?php echo $v['param'];?>" name="<?php echo $v['param'];?>" value="<?php if (isset($_POST[$v['param']])) echo $_POST[$v['param']];?>"/></td>
						<td><?php echo $v['type'];?></td>
						<td><?php echo $v['desc'];?></td>
					</tr>
<?php
	}
}
?>
				</table>
				<br/>
				<input type="submit" value="commit"/>
				</form>
			</div>
			<div id="middle_bottom"><?php isset($response) && pr($response);?></div>
		</div>
		<div id="right">
----------新增参数----------
			<div id="right_top">
				<table>
					<tr>
						<th>参数</th>
						<th>参数值</th>
						<th>操作</th>
					</tr>
					<tr>
						<td><input type="text" id="js_set_key" value=""/></td>
						<td><input type="text" id="js_set_value" value=""/></td>
						<td><input type="button" value="提交变更" onClick="modify_param($('#js_set_key').val(), $('#js_set_value').val());"/></td>
					</tr>
				</table>
			</div>
----------自动填充参数----------
			<div id="right_main"></div>
			<div id="right_bottom"></div>
		</div>
	</div>
</body>
</html>

<script>
	var auto_fill = true;//@TODO,自动填充
	
	function set_data() {
		
	}
	
	function get_data() {
		var url = site_url + '/tool_bar/get_data.php';
		$.get(url, {}, function(response) {
			$('#right_main').html(response);
			if (auto_fill == true) auto();
			});
	}

	function auto() {
		$(".js_right_data").each(function(){
			var value = $(this).val();
			var key = $(this).attr('name');
			$('#middle').find('.' + key).val(value);
		});
	}

	function modify_param(key, value) {
		var url = site_url +　'/tool_bar/set_data.php';
		var data = key + '|' + value;
		$.get(url, {'data':data}, function(response) {
			get_data();
		});
	}

	get_data();
</script>

<script>

var jsonObject= $.parseJSON('<?php echo $response;?>');
// var formatJsonStr=JSON.stringify(jsonObject,undefined, 2);
// $('#middle_bottom').html('<pre>' + formatJsonStr + '</pre>');
//group_list();
</script>

