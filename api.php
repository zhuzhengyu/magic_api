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
	public $class_name;
	
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
		
		if (isset($_GET['c']) && isset($_GET['m'])) {
			$this->selected_api = $_GET['m'];
			if ($this->selected_api) self::apiDetail();
		}
		
	}
	
	public function __destruct() {
		
	}
	
	private function projectList() {
		$project_list = scandir($this->customer_file_path);
		foreach ($project_list as $k => $v) {
			if (in_array($v, array('.', '..'))) unset($project_list[$k]);
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
	
	private function apiList() {
		$origin_classes = get_declared_classes();
		function __autoload($a) {
			$class = 'class ' . $a . '{}';
			eval($class);
		}
		include BASEPATH . '/' . $this->selected_file;
		$new_classes = get_declared_classes();
		$classes_diff = array_diff($new_classes, $origin_classes);
		foreach ($classes_diff as $v) {
			// 		if ($v == 'CI_Controller') continue;
			$method_list = get_class_methods($v);
			if ($method_list) {
				$useful_classes[$v] = $method_list;
			}
		}

		if (isset($useful_classes) && $useful_classes) {
			foreach ($useful_classes as $class_name => $method_list) {
				foreach ($method_list as $method) {
					$c_m = new ReflectionMethod($class_name, $method);
					$temp = $c_m->getDocComment();
					$temp = str_replace(array("\r\n", "\r", "\n"), '|R|', $temp);
					$t = explode("|R|", $temp);
					$t = str_replace("\t", ' ', $t);
					if (isset($t[1])) {
						$tt = explode(' ', $t[1]);
						foreach ($tt as $k => $v) {
							if ($v == ' ') unset($tt[$k]);
						}
						$interface[$method] = end($tt);
					}
				}
			}
		}
		$this->api_list = isset($interface) ? $interface : array();
		$this->class_name = $class_name;
	}
	
	private function apiDetail() {
		$class = $this->class_name;
		$method = $this->selected_api;
		$c_m = new ReflectionMethod($class, $method);
		
		$temp = $c_m->getDocComment();
		$temp = str_replace(array("\r\n", "\r", "\n"), '|R|', $temp);
		$a = explode("|R|", $temp);
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

// echo '-----project-----';
// pr($project_list);

// echo '-----file-----';
// pr($file_list);

// echo '-----api_list-----';
// pr($api_list);

// echo '-----api_detail-----';
// pr($api_detail);
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
						<th>项目</th>
						<th>操作</th>
					</tr>
<?php
if ($project_list) {
	foreach ($project_list as $k => $v) {
?>
					<tr>
						<td><a href="<?php echo $url . '?p=' . $v;?>"><?php echo $v;?></a></td>
						<td><a href="<?php echo 'set.php?p=' . $v;?>">编辑</a></td>
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
						<td><a href="<?php echo $url . '?p=' . $mApi->selected_project . '&f=' . $mApi->selected_file. '&c=' . $mApi->class_name . '&m=' . $k;?>"><?php echo $v;?></a></td>
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
			<div id="middle_bottom"><?php isset($response) && php_api\pr($response);?></div>
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

