<?php

namespace App\Lib;

/**
 * 返回一个类的全局静态实例
 */
class Single {

	/**
	 * 用于存放类的实例
	 */
	private static $instance;
	/**
	 * 获取一个的类的单一实例
	 * @param type $class 类名【注意：区分大小写，默认为ApiStatus】
	 * @param type $namespace 命名空间【默认为当前Single所在空间'\App\Lib'】
	 * @return type
	 */
	public static  function getInstance( $class='ApiStatus',$namespace='\App\Lib' ) {
		$className = '\\'.trim($namespace, '\\').'\\'.trim($class,'\\');
		if( isset(self::$instance[$className]) ){
			return self::$instance[$className];
		}
		if(class_exists($className) ) {
			self::$instance[$className] = new $className;
			return self::$instance[$className];
		}
		return false;
	}

}
