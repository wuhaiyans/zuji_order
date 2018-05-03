<?php
/**
 * Created by PhpStorm.
 * User: wuhaiyan
 * DateTime: 2018/5/3 11:41
 */
use Dingo\Api\Http\Request;
use Dingo\Api\Transformer\Binding;
use Dingo\Api\Contract\Transformer\Adapter;

class MyCustomTransformer implements Adapter
{
    public function transform($response, $transformer, Binding $binding, Request $request)
    {
        // Make a call to your transformation layer to transformer the given response.
    }
}