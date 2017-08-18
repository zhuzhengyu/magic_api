<?php
require 'magic_core.php';
$error_msg = '';

$config_arr['PROJECT']['name']			= '项目名';
$config_arr['PROJECT']['value']			= '';
$config_arr['PROJECT']['example']		= 'magic_zhu';

$config_arr['BASEPATH']['name']			= '欲生成api文档的类文件所在目录(非项目目录)';
$config_arr['BASEPATH']['value']		= '';
$config_arr['BASEPATH']['example']		= 'D:\work\workspace\magic_zhu';

$config_arr['TARGET_URL']['name']		= '目标基础URL地址';
$config_arr['TARGET_URL']['value']		= '';
$config_arr['TARGET_URL']['example']	= 'http:\/\/www.ctrip.com';

$config_arr['SUF_FILE']['name']			= '参与生成文档的文件后缀(多个用半角;分号分割)';
$config_arr['SUF_FILE']['value']		= 'php';
$config_arr['SUF_FILE']['example']		= 'php';

if (isset($_GET['a']) && $_GET['a'] == 'edit') {
	$data = file_get_contents('customer/' . $_GET['p'] . '/config.json');
	$data_arr = json_decode($data, true);
	foreach ($data_arr as $k => $v) {
		$config_arr[$k]['value'] = $v;
	}
} elseif (isset($_GET['a']) && $_GET['a'] == 'commit') {
	try {
		if (!(isset($_POST['PROJECT']) && isset($_POST['BASEPATH']) && isset($_POST['TARGET_URL']))) throw new \Exception(''); 
		if (!$_POST['PROJECT']) throw new \Exception('error: project!');
		if (!is_dir($_POST['BASEPATH'])) throw new \Exception('error: base path!');
		
		$context = stream_context_create(array(
				'http' => array(
						'timeout' => 3000 //超时时间，单位为秒
				)
		));
		
// 		if (!$_POST['TARGET_URL'] || !file_get_contents($_POST['TARGET_URL'], 0, $context)) throw new \Exception('error: target_url!');
		if (!$_POST['TARGET_URL']) throw new \Exception('error: target_url!');
		
		$data = $_POST;
		$file_path = 'customer/' . $_POST['PROJECT'];
		$file = $file_path . '/config.json';
		if (!file_exists($file_path)) mkdir($file_path);
		$contents = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		$result = file_put_contents($file, $contents);
		mkdir($file_path . '/api');
	} catch (\Exception $e) {
		$error_msg = $e->getMessage();
	}
	
	if (isset($result)) exit(header('Location: api.php'));
	
} elseif(isset($_GET['a']) && $_GET['a'] == 'delete') {
	$rmDirFile = function ($path) use (&$rmDirFile) {
		if (is_dir($path)) {
			$result = scandir($path);
			foreach ($result as $k => $v) {
				if (in_array($v, array('.', '..'))) continue;
				$rmDirFile($path . '/' . $v);
			}
			rmdir($path);
		} else {
			unlink($path);
		}
	};
	
	$rmDirFile('customer/' . $_GET['p']);
	exit(header('Location: api.php'));
} elseif($_GET['a'] == 'create') {
	
} else {
	$mApi = new magic_core;
	$project_list = $mApi->project_list;
	if ($project_list) exit(header('Location: api.php'));
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?php echo $title;?></title>
</head>
<body>
	<div id="main">
		<div id="left">
<?php if ($error_msg) echo $error_msg;?>
		</div>

		<form action="index.php?a=commit" method="POST">
		<div id="middle">
			<table>
				<tr>
					<th>配置项</th>
					<th>内容</th>
				</tr>
<?php
foreach ($config_arr as $k => $v) {
?>
				<tr>
					<td><?php echo $v['name'];?></td>
					<td><input type="text" name="<?php echo $k;?>" value="<?php echo $v['value'];?>" placeholder="<?php echo $v['example'];?>"/></td>
				</tr>
<?php
}
?>
			</table>
			<input type="submit" value="提交"/>
		</div>
		</form>

		<div id="right"></div>
	</div>
</body>
</html>
