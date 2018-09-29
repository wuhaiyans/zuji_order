<?php

/**
 * GivebackAddress 寄回地址临时配置
 *
 * @author wangjinlin
 */

namespace App\Order\Modules\Inc;


class GivebackAddressStatus {

    //--------------------------------------------------------------------------------------------
    //--+ 寄回地址临时配置 --------------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var array spu 对应寄回地址类别
     */
    const SPU_ADDRDSS_TYPE = [
        2=>2,
        211=>1,
        56=>1,
        47=>1,
        52=>1,
        53=>1,
        136=>1,
        137=>1,
        131=>1,
        130=>1,
        71=>1,
        54=>1,
        1396=>1,
        1323=>3,
        1327=>3,
        1316=>3,
        1314=>2,
        1309=>1,
        1306=>2,
        1290=>2,
        1269=>2,
        1264=>3,
        1259=>2,
        1237=>2,
        1235=>2,
        1233=>3,
        1205=>3,
        1196=>3,
        1190=>3,
        1187=>3,
        1184=>3,
        1181=>3,
        1175=>3,
        1169=>3,
        1165=>3,
        1160=>3,
        1148=>3,
        1142=>3,
        1135=>3,
        1129=>3,
        1120=>3,
        1111=>3,
        1105=>3,
        1100=>3,
        1096=>3,
        1091=>3,
        1087=>3,
        1082=>3,
        1078=>3,
        1075=>3,
        1069=>3,
        1061=>3,
        1054=>3,
        1048=>3,
        1042=>3,
        1036=>3,
        1033=>3,
        1028=>3,
        1024=>3,
        947=>3,
        944=>3,
        941=>3,
        938=>3,
        935=>3,
        929=>3,
        923=>3,
        917=>3,
        911=>1,
        900=>2,
        868=>2,
        867=>2,
        866=>2,
        865=>2,
        755=>3,
        754=>3,
        747=>2,
        743=>2,
        736=>2,
        729=>3,
        728=>3,
        722=>2,
        721=>2,
        720=>2,
        719=>2,
        718=>2,
        717=>2,
        716=>2,
        715=>2,
        714=>2,
        713=>2,
        701=>3,
        698=>1,
        697=>1,
        696=>1,
        695=>1,
        694=>1,
        693=>1,
        692=>1,
        691=>1,
        690=>1,
        689=>1,
        688=>1,
        687=>1,
        686=>1,
        685=>1,
        684=>1,
        681=>1,
        680=>1,
        663=>1,
        662=>1,
        661=>1,
        660=>1,
        646=>1,
        634=>2,
        630=>2,
        603=>3,
        602=>2,
        601=>2,
        600=>2,
        596=>2,
        575=>2,
        574=>2,
        573=>2,
        572=>2,
        565=>2,
        564=>2,
        563=>2,
        480=>2,
        479=>2,
        478=>2,
        477=>2,
        476=>2,
        475=>2,
        474=>2,
        473=>2,
        470=>1,
        468=>1,
        467=>1,
        466=>1,
        391=>1,
        388=>1,
        386=>1,
        378=>1,
        333=>1,
        235=>1,
        227=>1,
        222=>1,
        214=>1,
        207=>2,
        206=>2,
        203=>2,
        200=>2,
        170=>1,
        169=>1,
        168=>1,
        167=>1,
        158=>1,
        157=>1,
        155=>1,
        154=>1,
        153=>1,
        152=>1,
        151=>1,
        150=>1,
        149=>1,
        148=>1,
        147=>1,
        146=>1,
        145=>1,
        144=>1,
        143=>1,
        129=>1,
        127=>1,
        123=>1,
        122=>1,
        121=>1,
        120=>1,
        119=>1,
        117=>1,
        116=>1,
        113=>1,
        82=>1,
        81=>1,
        78=>1,
        65=>1,
        377=>1,
        66=>1,
        138=>1,
        60=>1,
        133=>1,
        568=>1,
        567=>1,
        240=>1,
        125=>1,
        383=>1,
        241=>1,
        566=>1,
        139=>1,
        647=>1,
        77=>1,
        1236=>2,
        594=>2,
        142=>1,
        114=>1,
        51=>1,
        48=>1,
        605=>1,
        437=>1,
        156=>1,
        126=>1,
        141=>1,
        626=>1,
        625=>1,
        648=>1,
        649=>1,
        650=>1,
        1282=>2,
        1291=>2,
        1277=>2,
        1268=>2,
        1287=>1,
        1320=>1,
        1319=>1,
        1321=>1
    ];
    /**
     * @var array 类型
     */
    const ADDRESS_TYPE = [
        1=>[
            'type'=>'所有手机',
            'address'=>'深圳市龙华区龙观东路28号桦浩泰（宏恒谷）工业区A栋3楼',
            'addressee'=>'拿趣用租赁',
            'phone'=>'13268283210'
        ],
        2=>[
            'type'=>'大疆无人机、极米投影仪、天猫魔屏、雅萌、日立、refa、小米9号平衡车plus、佳能700D、甲醛检测仪、小米扫地机器人、小米石头扫地机器人、戴森吸尘器、戴森吹风机',
            'address'=>'北京市朝阳区来广营朝来科技园18号院16号楼5楼',
            'addressee'=>'拿趣用租赁',
            'phone'=>'01053384531'
        ],
        3=>[
            'type'=>'科大讯飞翻译机、任天堂游戏机、任天堂游戏卡、小米9号平衡车、苹果笔记本、谷歌平板、搜狗翻译机',
            'address'=>'北京市海淀区中关村大街11号e世界财富中心A座B2 P2联合创业办公社',
            'addressee'=>'吴际',
            'phone'=>'15210998430'
        ]
    ];



    /**
     * 获取长租月数选项
     *
     * @return array
     */
    public static function getGivebackAddress($spu_id){
        $address = [];
        if(!$spu_id) return $address;


        $type = self::SPU_ADDRDSS_TYPE[$spu_id];
        if($type){
            $address['giveback_address'] = self::ADDRESS_TYPE[$type]['addressee'];
            $address['giveback_username'] = self::ADDRESS_TYPE[$type]['phone'];
            $address['giveback_tel'] = self::ADDRESS_TYPE[$type]['address'];

        }else{
            //默认地址
            $address['giveback_address'] = self::ADDRESS_TYPE[1]['addressee'];
            $address['giveback_username'] = self::ADDRESS_TYPE[1]['phone'];
            $address['giveback_tel'] = self::ADDRESS_TYPE[1]['address'];
        }
        return $address;

    }

}
