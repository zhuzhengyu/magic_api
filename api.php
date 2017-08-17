<?php
require 'magic_core.php';
require 'config.php';

$mApi = new magic_core;

$project_list = $mApi->project_list;
if (!$project_list) exit(header('Location: index.php'));
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

