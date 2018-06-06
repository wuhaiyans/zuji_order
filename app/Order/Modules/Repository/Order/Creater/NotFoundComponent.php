<?php
/**
 */

namespace App\Order\Modules\Repository\Order\Creater;



class NotFoundComponent extends \Exception
{

    /**
     * 组件名称
     * @var string
     */
    private $name = '';

    public function __construct($name = "")
    {
        parent::__construct('未定义组件');
    }

    /**
     * 获取未定义的组件名称
     * @return string
     */
    public function getComponentName(){
        return $this->name;
    }

}