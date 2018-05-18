<?php
/**
 * Configurable
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 * 
 */

namespace App\Lib;

/**
 * Configurable 可配置类
 * 非private属性才可以配置
 * 【注意：】
 *      继承规则：
 *      1）子类属性访问控制权限最小为 protected，（子类的private属性，在父类方法中无法设置）
 *
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class Configurable {
    
	
	/**
	 * 可配置的 构造函数
	 * @param	array	$data		// 关联数组，键为属性名称
     * @throws PropertyException
	 */
	public function __construct( array $data=[] ) {
		$this->config($data);
	}
    
    /**
     * 配置对象的属性
     * @param array $params
     * @throws PropertyException
     */
    public function config( $params, &$throwException=true ){
        $temp = true;
        $exc = &$throwException;
        foreach( $params as $property=>$v ){
            if( $throwException === true ){// 抛出异常时
                $temp = true;// 还原标识为 抛出异常
            }else{
                $temp = false;
            }
            if( !$this->setProperty($property, $v, $temp)  ){
                $exc = $temp;
            }
        }
        $throwException = $exc;
        $this->init();// 配置成功后的初始化工作
    }
	
    /**
     * 配置成功后的初始化工作
     * config()成功配置结束时触发
     */
    protected function init(){
        
    }


    /**
     * 设置属性值（非private属性）
     * @param string $property
     * @param mixed $value
     * @param mixed $throwException
     * @return boolean
     * @throws PropertyException
     */
    public function setProperty( $property, $value, &$throwException=true ){
        // 确认属性是否存在（双重判断，第一层捕获异常）
        if( !$this->_ensure_property($property, $exception) ){
			// 转换属性格式：下划线去掉，后一个字母大写
			$property = lcfirst( str_replace('_','',ucwords($property, '_') ) );
			if( !$this->_ensure_property($property, $throwException) ){
				return false;
			}
        }
        // 属性赋值
        $this->$property = $value;
        
        return true;
    }
    public function __set($property, $value) {
        $this->$property = $value;
    }
    
    /**
     * 获取属性值（非private属性）
     * @param string $property
     * @param mixed $throwException
     * @return mixed 属性存在，返回属性值；属性不存在，如果指定了$throwException，则返回NULL，如果没有指定$throwException或$throwException===true，则抛出异常
     * @throws PropertyException
     */
    public function getProperty( $property, &$throwException=true ){
        // 检查属性是否存在
        if( !$this->_ensure_property($property, $throwException) ){
            return NULL;
        }
        // 返回属性值
        return $this->$property;
    }
    
    /**
     * 判断实现是否定义
     * @param string $property
     * @return boolean true：属性存在；false：属性不存在
     */
    public function propertyExists( $property ){
        // 检查属性是否存在
        if( !property_exists($this, $property) ){
            return false;
        }
        return true;
    }
    
    /**
     * 确定属性符合可配置性(1.存在，2，非private修饰)
     * @param type $property
     * @param type $throwException 抛出异常标识
     *  true:   抛出异常
     *  其他情况： 通过参数的引用特性，讲异常赋予实参
     * @return boolean true：符合；false：不符合
     * @throws PropertyException
     * 
     * 例如：假设当前对象没有非private的t1属性，可以用以下两种方式处理
     * ->_ensure_property('t1','123');      // 抛出异常
     * ->_ensure_property('t1','123',$exc); // 返回false，并将当前的异常赋予实参$exc
     * 
     * 例如：假设当前对象存在非private的t2属性，
     * ->_ensure_property('t1','123');      // 返回 true
     * ->_ensure_property('t1','123',$exc); // 返回 true，并将NULL赋予实参$exc
     * 
     */
    private function _ensure_property( $property, &$throwException=true ){
        // 异常或错误标识
        $flagExc = false;
        // 提示内容
        $msg = '';
        try {
            $class = get_class($this);
            // 属性反射
            $reflection = new \ReflectionProperty( $class, $property );// 可能抛出“类不存在该属性”的异常
            // 判断属性访问修饰符，必须是非private属性，否则不符合可配置性
            if( $reflection->isPrivate() ){
                $flagExc = true;
                $msg = 'Cannot access private property '. $class .':$'.$property;
            }
        } catch (\ReflectionException $exc) {
            $flagExc = true;
            $msg = $exc->getMessage();
        }
        // 存在错误或异常
        if( $flagExc ){
            // 创建异常
            $temp = new PropertyException($msg);
            // 判断是否可以抛出，只有（$throwException === true）才抛出异常
            if( $throwException === true ){// 必须是全等
                throw $temp;
            }
            // 不抛出异常，通过引用实参$throwException，传递当前异常
            $throwException = $temp;
            // 返回结果，不符合
            return false;
        }
        // 不存在错误或异常，引用实参$throwException赋值为NULL
        $throwException = NULL;
        // 返回结果，符合
        return true;
    }
    
}
