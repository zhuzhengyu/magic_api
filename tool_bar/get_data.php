<?php

// pr($_COOKIE);


function pr($var) {
	echo '<pre>';
	print_r($var);
	echo '</pre>';
}

?>

<table>
  <tr>
    <th>参数</th>
    <th>值</th>
    <th>操作</th>
  </tr>
<?php
foreach ($_COOKIE as $k => $v) {
?>
  <tr>
    <td><?php echo $k;?></td>
    <td><input type="text" class="<?php echo $k;?> js_right_data" name="<?php echo $k;?>" value="<?php echo $v;?>"/></td>
    <td>
    	<input type="button" name="" value="remove" onClick="modify_param('<?php echo $k;?>', '');"/>
    </td>
  </tr>
<?php
}
?>
</table>