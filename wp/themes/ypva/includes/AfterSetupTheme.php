<?php

class kotlis_AfterSetupTheme{
	
	
	static function return_thme_option($string,$str=null){
		global $kotlis;
		if($str!=null)
		return isset($kotlis[''.$string.''][''.$str.'']) ? $kotlis[''.$string.''][''.$str.''] : null;
		else
		return isset($kotlis[''.$string.'']) ? $kotlis[''.$string.''] : null;
	}
	
	
}
?>