<?php
/**
 * 导出商品图片信息并修改
 *
 * User: wangjinlin
 * Date: 2018/6/11
 * Time: 下午5:05
 */

$stime=microtime(true);
$num = 0;//统计插入条数
$sel = 0;//统计查询条数

$t = time();//初始化时间
$_t = 0;//上次执行时间
//$spu_ID_S = 0;//商品开始ID 初始化为0
//$spu_ID_N = 1000000;//商品结束ID (必填)
$str_l = "https://s1.huishoubao.com/zuji/images/content/";//要查找的值
$str_x = "https://www.baidu.com/";//要替换的值

//要查找(key)并替换(value)的数组
$str_all = [
'https://s1.huishoubao.com/zuji/images/content/153389485273631.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153389485273631.png',
'https://s1.huishoubao.com/zuji/images/content/153389461362910.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153389461362910.jpg',
'https://s1.huishoubao.com/zuji/images/content/153389485388762.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153389485388762.jpg',
'https://s1.huishoubao.com/zuji/images/content/153389208715226.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153389208715226.jpg',
'https://s1.huishoubao.com/zuji/images/content/153389208640201.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153389208640201.jpg',
'https://s1.huishoubao.com/zuji/images/content/153389461264543.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153389461264543.jpg',
'https://s1.huishoubao.com/zuji/images/content/153388713385441.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153388713385441.jpg',
'https://s1.huishoubao.com/zuji/images/content/153388621780934.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153388621780934.jpg',
'https://s1.huishoubao.com/zuji/images/content/153257600790630.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153257600790630.png',
'https://s1.huishoubao.com/zuji/images/content/153257612194799.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153257612194799.png',
'https://s1.huishoubao.com/zuji/images/content/153388621468731.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153388621468731.jpg',
'https://s1.huishoubao.com/zuji/images/content/153257620081351.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153257620081351.png',
'https://s1.huishoubao.com/zuji/images/content/153257616573104.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153257616573104.png',
'https://s1.huishoubao.com/zuji/images/content/153388639215636.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153388639215636.jpg',
'https://s1.huishoubao.com/zuji/images/content/153240473246995.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153240473246995.png',
'https://s1.huishoubao.com/zuji/images/content/153251077737420.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153251077737420.jpg',
'https://s1.huishoubao.com/zuji/images/content/153216174912025.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153216174912025.png',
'https://s1.huishoubao.com/zuji/images/content/153240387147635.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153240387147635.jpg',
'https://s1.huishoubao.com/zuji/images/content/153216327327819.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153216327327819.png',
'https://s1.huishoubao.com/zuji/images/content/153215972015773.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153215972015773.jpg',
'https://s1.huishoubao.com/zuji/images/content/153215953811400.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153215953811400.jpg',
'https://s1.huishoubao.com/zuji/images/content/153147368319898.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153147368319898.png',
'https://s1.huishoubao.com/zuji/images/content/153215954390226.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153215954390226.jpg',
'https://s1.huishoubao.com/zuji/images/content/153215791754954.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153215791754954.jpg',
'https://s1.huishoubao.com/zuji/images/content/153215972726572.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153215972726572.jpg',
'https://s1.huishoubao.com/zuji/images/content/153240386454938.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153240386454938.jpg',
'https://s1.huishoubao.com/zuji/images/content/153215972976348.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153215972976348.jpg',
'https://s1.huishoubao.com/zuji/images/content/153147359791376.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153147359791376.png',
'https://s1.huishoubao.com/zuji/images/content/153146898682863.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153146898682863.png',
'https://s1.huishoubao.com/zuji/images/content/153215791267175.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153215791267175.jpg',
'https://s1.huishoubao.com/zuji/images/content/153145142189291.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153145142189291.png',
'https://s1.huishoubao.com/zuji/images/content/153147352373112.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153147352373112.png',
'https://s1.huishoubao.com/zuji/images/content/153077959126876.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153077959126876.png',
'https://s1.huishoubao.com/zuji/images/content/153146659726187.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153146659726187.jpg',
'https://s1.huishoubao.com/zuji/images/content/153001009646853.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153001009646853.png',
'https://s1.huishoubao.com/zuji/images/content/152966265637687.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152966265637687.png',
'https://s1.huishoubao.com/zuji/images/content/152965749448146.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152965749448146.png',
'https://s1.huishoubao.com/zuji/images/content/153077790171615.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153077790171615.png',
'https://s1.huishoubao.com/zuji/images/content/152966281328748.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152966281328748.png',
'https://s1.huishoubao.com/zuji/images/content/153092738553699.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153092738553699.jpg',
'https://s1.huishoubao.com/zuji/images/content/152965565061820.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152965565061820.png',
'https://s1.huishoubao.com/zuji/images/content/152958538968949.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152958538968949.png',
'https://s1.huishoubao.com/zuji/images/content/152958539192824.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152958539192824.jpg',
'https://s1.huishoubao.com/zuji/images/content/152957876699479.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152957876699479.png',
'https://s1.huishoubao.com/zuji/images/content/152957824871015.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152957824871015.png',
'https://s1.huishoubao.com/zuji/images/content/152957782099916.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152957782099916.png',
'https://s1.huishoubao.com/zuji/images/content/152957827210199.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152957827210199.png',
'https://s1.huishoubao.com/zuji/images/content/152957716367206.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152957716367206.jpg',
'https://s1.huishoubao.com/zuji/images/content/152895720782080.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152895720782080.png',
'https://s1.huishoubao.com/zuji/images/content/152957670628367.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152957670628367.png',
'https://s1.huishoubao.com/zuji/images/content/152956615150861.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152956615150861.png',
'https://s1.huishoubao.com/zuji/images/content/152940922730172.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152940922730172.jpg',
'https://s1.huishoubao.com/zuji/images/content/152895627293892.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152895627293892.png',
'https://s1.huishoubao.com/zuji/images/content/152895806141351.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152895806141351.png',
'https://s1.huishoubao.com/zuji/images/content/152895623621733.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152895623621733.png',
'https://s1.huishoubao.com/zuji/images/content/152895660791090.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152895660791090.png',
'https://s1.huishoubao.com/zuji/images/content/152894860431646.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152894860431646.png',
'https://s1.huishoubao.com/zuji/images/content/152894844075308.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152894844075308.png',
'https://s1.huishoubao.com/zuji/images/content/152894781645572.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152894781645572.png',
'https://s1.huishoubao.com/zuji/images/content/152889616612678.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152889616612678.png',
'https://s1.huishoubao.com/zuji/images/content/152894841356012.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152894841356012.png',
'https://s1.huishoubao.com/zuji/images/content/152894801791401.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152894801791401.png',
'https://s1.huishoubao.com/zuji/images/content/152894718266351.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152894718266351.png',
'https://s1.huishoubao.com/zuji/images/content/152894695990700.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152894695990700.png',
'https://s1.huishoubao.com/zuji/images/content/152889537612569.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152889537612569.png',
'https://s1.huishoubao.com/zuji/images/content/152894675425029.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152894675425029.png',
'https://s1.huishoubao.com/zuji/images/content/152894671875061.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152894671875061.png',
'https://s1.huishoubao.com/zuji/images/content/152889661984918.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152889661984918.png',
'https://s1.huishoubao.com/zuji/images/content/152894667661580.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152894667661580.png',
'https://s1.huishoubao.com/zuji/images/content/152894658794559.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152894658794559.png',
'https://s1.huishoubao.com/zuji/images/content/152894662540564.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152894662540564.png',
'https://s1.huishoubao.com/zuji/images/content/152889608080027.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152889608080027.jpg',
'https://s1.huishoubao.com/zuji/images/content/152889471597746.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152889471597746.png',
'https://s1.huishoubao.com/zuji/images/content/152889407850486.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152889407850486.png',
'https://s1.huishoubao.com/zuji/images/content/152889372735420.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152889372735420.png',
'https://s1.huishoubao.com/zuji/images/content/152889403876277.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152889403876277.jpg',
'https://s1.huishoubao.com/zuji/images/content/152889456416771.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152889456416771.jpg',
'https://s1.huishoubao.com/zuji/images/content/152889273275507.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152889273275507.png',
'https://s1.huishoubao.com/zuji/images/content/151736997037250.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151736997037250.jpg',
'https://s1.huishoubao.com/zuji/images/content/152889273452945.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152889273452945.jpg',
'https://s1.huishoubao.com/zuji/images/content/152889234089899.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152889234089899.jpg',
'https://s1.huishoubao.com/zuji/images/content/152889365648060.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152889365648060.jpg',
'https://s1.huishoubao.com/zuji/images/content/152889167412808.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152889167412808.png',
'https://s1.huishoubao.com/zuji/images/content/152889233345632.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152889233345632.png',
'https://s1.huishoubao.com/zuji/images/content/152888695350761.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152888695350761.png',
'https://s1.huishoubao.com/zuji/images/content/152886211678957.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152886211678957.png',
'https://s1.huishoubao.com/zuji/images/content/152889114954718.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152889114954718.png',
'https://s1.huishoubao.com/zuji/images/content/152888956274126.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152888956274126.png',
'https://s1.huishoubao.com/zuji/images/content/152886128071178.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152886128071178.jpg',
'https://s1.huishoubao.com/zuji/images/content/152886212286743.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152886212286743.jpg',
'https://s1.huishoubao.com/zuji/images/content/152886186715578.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152886186715578.png',
'https://s1.huishoubao.com/zuji/images/content/152886261591325.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152886261591325.jpg',
'https://s1.huishoubao.com/zuji/images/content/152870583748887.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152870583748887.png',
'https://s1.huishoubao.com/zuji/images/content/152880097377437.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152880097377437.png',
'https://s1.huishoubao.com/zuji/images/content/152886211955673.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152886211955673.jpg',
'https://s1.huishoubao.com/zuji/images/content/152870496649669.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152870496649669.png',
'https://s1.huishoubao.com/zuji/images/content/152886127911914.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152886127911914.jpg',
'https://s1.huishoubao.com/zuji/images/content/152880090261004.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152880090261004.png',
'https://s1.huishoubao.com/zuji/images/content/152880078981061.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152880078981061.png',
'https://s1.huishoubao.com/zuji/images/content/152880072050906.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152880072050906.png',
'https://s1.huishoubao.com/zuji/images/content/152880083722816.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152880083722816.png',
'https://s1.huishoubao.com/zuji/images/content/152870442442140.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152870442442140.png',
'https://s1.huishoubao.com/zuji/images/content/152870449617615.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152870449617615.png',
'https://s1.huishoubao.com/zuji/images/content/152870460113570.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152870460113570.png',
'https://s1.huishoubao.com/zuji/images/content/152870455188594.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152870455188594.png',
'https://s1.huishoubao.com/zuji/images/content/152870452332666.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152870452332666.png',
'https://s1.huishoubao.com/zuji/images/content/152868856915253.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152868856915253.png',
'https://s1.huishoubao.com/zuji/images/content/152868879111043.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152868879111043.png',
'https://s1.huishoubao.com/zuji/images/content/152868899064419.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152868899064419.png',
'https://s1.huishoubao.com/zuji/images/content/152868875595755.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152868875595755.png',
'https://s1.huishoubao.com/zuji/images/content/152868823846488.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152868823846488.png',
'https://s1.huishoubao.com/zuji/images/content/152868834835695.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152868834835695.png',
'https://s1.huishoubao.com/zuji/images/content/152868781939366.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152868781939366.png',
'https://s1.huishoubao.com/zuji/images/content/152868818951357.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152868818951357.png',
'https://s1.huishoubao.com/zuji/images/content/152868785734643.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152868785734643.png',
'https://s1.huishoubao.com/zuji/images/content/152834166911785.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152834166911785.jpg',
'https://s1.huishoubao.com/zuji/images/content/152834120069017.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152834120069017.jpg',
'https://s1.huishoubao.com/zuji/images/content/152818395927832.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152818395927832.png',
'https://s1.huishoubao.com/zuji/images/content/152834120578099.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152834120578099.jpg',
'https://s1.huishoubao.com/zuji/images/content/152818511585268.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152818511585268.png',
'https://s1.huishoubao.com/zuji/images/content/152818396025020.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152818396025020.png',
'https://s1.huishoubao.com/zuji/images/content/152834120980561.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152834120980561.jpg',
'https://s1.huishoubao.com/zuji/images/content/152818511456855.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152818511456855.png',
'https://s1.huishoubao.com/zuji/images/content/152834092072261.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152834092072261.jpg',
'https://s1.huishoubao.com/zuji/images/content/152775843714893.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152775843714893.jpg',
'https://s1.huishoubao.com/zuji/images/content/152775818765845.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152775818765845.jpg',
'https://s1.huishoubao.com/zuji/images/content/152758196081899.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152758196081899.png',
'https://s1.huishoubao.com/zuji/images/content/152721917593258.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152721917593258.png',
'https://s1.huishoubao.com/zuji/images/content/152721912176615.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152721912176615.png',
'https://s1.huishoubao.com/zuji/images/content/152721908819264.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152721908819264.png',
'https://s1.huishoubao.com/zuji/images/content/152758186040622.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152758186040622.png',
'https://s1.huishoubao.com/zuji/images/content/152775818313055.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152775818313055.jpg',
'https://s1.huishoubao.com/zuji/images/content/152707441519401.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152707441519401.png',
'https://s1.huishoubao.com/zuji/images/content/152721797436648.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152721797436648.png',
'https://s1.huishoubao.com/zuji/images/content/152707444243462.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152707444243462.png',
'https://s1.huishoubao.com/zuji/images/content/152721897765918.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152721897765918.png',
'https://s1.huishoubao.com/zuji/images/content/152721894662007.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152721894662007.png',
'https://s1.huishoubao.com/zuji/images/content/152721795011471.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152721795011471.png',
'https://s1.huishoubao.com/zuji/images/content/152689178129491.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152689178129491.jpg',
'https://s1.huishoubao.com/zuji/images/content/152593432744278.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152593432744278.png',
'https://s1.huishoubao.com/zuji/images/content/152568482355692.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152568482355692.png',
'https://s1.huishoubao.com/zuji/images/content/152593536780289.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152593536780289.png',
'https://s1.huishoubao.com/zuji/images/content/152593442790963.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152593442790963.png',
'https://s1.huishoubao.com/zuji/images/content/152593544720675.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152593544720675.png',
'https://s1.huishoubao.com/zuji/images/content/152574985642109.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152574985642109.png',
'https://s1.huishoubao.com/zuji/images/content/152591904581213.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152591904581213.jpg',
'https://s1.huishoubao.com/zuji/images/content/152585983418396.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152585983418396.jpg',
'https://s1.huishoubao.com/zuji/images/content/152569605774574.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152569605774574.jpg',
'https://s1.huishoubao.com/zuji/images/content/152574553371742.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152574553371742.jpg',
'https://s1.huishoubao.com/zuji/images/content/152568481528795.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152568481528795.png',
'https://s1.huishoubao.com/zuji/images/content/152570056975329.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152570056975329.png',
'https://s1.huishoubao.com/zuji/images/content/152570056798778.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152570056798778.png',
'https://s1.huishoubao.com/zuji/images/content/152570056381022.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152570056381022.png',
'https://s1.huishoubao.com/zuji/images/content/152566444650579.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152566444650579.jpg',
'https://s1.huishoubao.com/zuji/images/content/152447178334509.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152447178334509.jpg',
'https://s1.huishoubao.com/zuji/images/content/152447178285666.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152447178285666.jpg',
'https://s1.huishoubao.com/zuji/images/content/152568480332259.JPG'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152568480332259.JPG',
'https://s1.huishoubao.com/zuji/images/content/152569605456373.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152569605456373.jpg',
'https://s1.huishoubao.com/zuji/images/content/152447177794064.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152447177794064.jpg',
'https://s1.huishoubao.com/zuji/images/content/152422098546003.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152422098546003.png',
'https://s1.huishoubao.com/zuji/images/content/152421482756870.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152421482756870.png',
'https://s1.huishoubao.com/zuji/images/content/152440379997489.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152440379997489.jpg',
'https://s1.huishoubao.com/zuji/images/content/152440373147789.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152440373147789.jpg',
'https://s1.huishoubao.com/zuji/images/content/152422419458413.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152422419458413.png',
'https://s1.huishoubao.com/zuji/images/content/152395187586194.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152395187586194.png',
'https://s1.huishoubao.com/zuji/images/content/152440368237317.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152440368237317.jpg',
'https://s1.huishoubao.com/zuji/images/content/152395190217889.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152395190217889.png',
'https://s1.huishoubao.com/zuji/images/content/152395195168972.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152395195168972.png',
'https://s1.huishoubao.com/zuji/images/content/152393322645276.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152393322645276.png',
'https://s1.huishoubao.com/zuji/images/content/152395180612414.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152395180612414.png',
'https://s1.huishoubao.com/zuji/images/content/152395192644722.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152395192644722.png',
'https://s1.huishoubao.com/zuji/images/content/152395178154807.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152395178154807.png',
'https://s1.huishoubao.com/zuji/images/content/152372086028557.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152372086028557.jpg',
'https://s1.huishoubao.com/zuji/images/content/152362006463975.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152362006463975.jpg',
'https://s1.huishoubao.com/zuji/images/content/152361140679734.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152361140679734.png',
'https://s1.huishoubao.com/zuji/images/content/152362006663171.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152362006663171.jpg',
'https://s1.huishoubao.com/zuji/images/content/152362006135189.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152362006135189.jpg',
'https://s1.huishoubao.com/zuji/images/content/152372086221943.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152372086221943.jpg',
'https://s1.huishoubao.com/zuji/images/content/152362006863710.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152362006863710.jpg',
'https://s1.huishoubao.com/zuji/images/content/152344554018821.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152344554018821.jpg',
'https://s1.huishoubao.com/zuji/images/content/152344554377378.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152344554377378.jpg',
'https://s1.huishoubao.com/zuji/images/content/152344553728257.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152344553728257.jpg',
'https://s1.huishoubao.com/zuji/images/content/152358664138859.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152358664138859.jpg',
'https://s1.huishoubao.com/zuji/images/content/152344423930598.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152344423930598.jpg',
'https://s1.huishoubao.com/zuji/images/content/152344424149958.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152344424149958.jpg',
'https://s1.huishoubao.com/zuji/images/content/152344424823456.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152344424823456.jpg',
'https://s1.huishoubao.com/zuji/images/content/152344480284357.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152344480284357.jpg',
'https://s1.huishoubao.com/zuji/images/content/152332599622665.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152332599622665.jpg',
'https://s1.huishoubao.com/zuji/images/content/152264965279110.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152264965279110.png',
'https://s1.huishoubao.com/zuji/images/content/152344480521186.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152344480521186.jpg',
'https://s1.huishoubao.com/zuji/images/content/152344479929372.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152344479929372.jpg',
'https://s1.huishoubao.com/zuji/images/content/152344479257408.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152344479257408.jpg',
'https://s1.huishoubao.com/zuji/images/content/152344424519077.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152344424519077.jpg',
'https://s1.huishoubao.com/zuji/images/content/152316843693779.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152316843693779.png',
'https://s1.huishoubao.com/zuji/images/content/152316839870448.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152316839870448.png',
'https://s1.huishoubao.com/zuji/images/content/152332597694608.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152332597694608.jpg',
'https://s1.huishoubao.com/zuji/images/content/152264059370248.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152264059370248.png',
'https://s1.huishoubao.com/zuji/images/content/152264137063956.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152264137063956.png',
'https://s1.huishoubao.com/zuji/images/content/152263905688278.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152263905688278.png',
'https://s1.huishoubao.com/zuji/images/content/152265003615145.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152265003615145.png',
'https://s1.huishoubao.com/zuji/images/content/152248609512047.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248609512047.png',
'https://s1.huishoubao.com/zuji/images/content/152248563812762.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248563812762.png',
'https://s1.huishoubao.com/zuji/images/content/152248556033798.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248556033798.png',
'https://s1.huishoubao.com/zuji/images/content/152248607190790.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248607190790.png',
'https://s1.huishoubao.com/zuji/images/content/152263938119010.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152263938119010.png',
'https://s1.huishoubao.com/zuji/images/content/152248542573671.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248542573671.png',
'https://s1.huishoubao.com/zuji/images/content/152248552442439.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248552442439.png',
'https://s1.huishoubao.com/zuji/images/content/152248549569335.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248549569335.png',
'https://s1.huishoubao.com/zuji/images/content/152248560327136.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248560327136.png',
'https://s1.huishoubao.com/zuji/images/content/152248469987415.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248469987415.png',
'https://s1.huishoubao.com/zuji/images/content/152248445142580.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248445142580.png',
'https://s1.huishoubao.com/zuji/images/content/152248443210274.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248443210274.png',
'https://s1.huishoubao.com/zuji/images/content/152248466993799.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248466993799.png',
'https://s1.huishoubao.com/zuji/images/content/152248437415702.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248437415702.png',
'https://s1.huishoubao.com/zuji/images/content/152248398042845.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248398042845.png',
'https://s1.huishoubao.com/zuji/images/content/152248321494106.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248321494106.png',
'https://s1.huishoubao.com/zuji/images/content/152248405670619.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248405670619.png',
'https://s1.huishoubao.com/zuji/images/content/152248341532270.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248341532270.png',
'https://s1.huishoubao.com/zuji/images/content/152248369612782.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248369612782.png',
'https://s1.huishoubao.com/zuji/images/content/152248356843300.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248356843300.png',
'https://s1.huishoubao.com/zuji/images/content/152248299336861.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248299336861.png',
'https://s1.huishoubao.com/zuji/images/content/152248290246704.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248290246704.png',
'https://s1.huishoubao.com/zuji/images/content/152248287642576.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248287642576.png',
'https://s1.huishoubao.com/zuji/images/content/152248278242096.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248278242096.png',
'https://s1.huishoubao.com/zuji/images/content/152248253876405.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248253876405.png',
'https://s1.huishoubao.com/zuji/images/content/152248268240378.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248268240378.png',
'https://s1.huishoubao.com/zuji/images/content/152248286098168.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248286098168.png',
'https://s1.huishoubao.com/zuji/images/content/152248258123188.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248258123188.png',
'https://s1.huishoubao.com/zuji/images/content/152248217888016.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248217888016.png',
'https://s1.huishoubao.com/zuji/images/content/152248282071448.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248282071448.png',
'https://s1.huishoubao.com/zuji/images/content/152248209022455.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248209022455.png',
'https://s1.huishoubao.com/zuji/images/content/152248206076318.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248206076318.png',
'https://s1.huishoubao.com/zuji/images/content/152248187230043.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248187230043.png',
'https://s1.huishoubao.com/zuji/images/content/152248185210365.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248185210365.png',
'https://s1.huishoubao.com/zuji/images/content/152248203160080.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248203160080.png',
'https://s1.huishoubao.com/zuji/images/content/152248140278673.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248140278673.png',
'https://s1.huishoubao.com/zuji/images/content/152248181530767.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248181530767.png',
'https://s1.huishoubao.com/zuji/images/content/152248149977413.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248149977413.png',
'https://s1.huishoubao.com/zuji/images/content/152247982623013.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152247982623013.png',
'https://s1.huishoubao.com/zuji/images/content/152248054776547.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248054776547.png',
'https://s1.huishoubao.com/zuji/images/content/152247948938304.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152247948938304.png',
'https://s1.huishoubao.com/zuji/images/content/152248038930746.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248038930746.png',
'https://s1.huishoubao.com/zuji/images/content/152248062752455.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152248062752455.png',
'https://s1.huishoubao.com/zuji/images/content/152247909982576.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152247909982576.png',
'https://s1.huishoubao.com/zuji/images/content/152247888799578.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152247888799578.png',
'https://s1.huishoubao.com/zuji/images/content/152247914751898.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152247914751898.png',
'https://s1.huishoubao.com/zuji/images/content/152247900396941.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152247900396941.png',
'https://s1.huishoubao.com/zuji/images/content/152247885551616.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152247885551616.png',
'https://s1.huishoubao.com/zuji/images/content/152247831592357.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152247831592357.png',
'https://s1.huishoubao.com/zuji/images/content/152247840988041.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152247840988041.png',
'https://s1.huishoubao.com/zuji/images/content/152238321796907.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152238321796907.png',
'https://s1.huishoubao.com/zuji/images/content/152247871044526.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152247871044526.png',
'https://s1.huishoubao.com/zuji/images/content/152247853960958.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152247853960958.png',
'https://s1.huishoubao.com/zuji/images/content/152239351729098.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152239351729098.jpg',
'https://s1.huishoubao.com/zuji/images/content/152239350923409.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152239350923409.jpg',
'https://s1.huishoubao.com/zuji/images/content/152222218873933.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152222218873933.jpg',
'https://s1.huishoubao.com/zuji/images/content/152189924256593.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152189924256593.jpg',
'https://s1.huishoubao.com/zuji/images/content/152222217587278.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152222217587278.jpg',
'https://s1.huishoubao.com/zuji/images/content/152189874843557.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152189874843557.jpg',
'https://s1.huishoubao.com/zuji/images/content/152189874879057.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152189874879057.jpg',
'https://s1.huishoubao.com/zuji/images/content/152189924141067.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152189924141067.jpg',
'https://s1.huishoubao.com/zuji/images/content/152189838046636.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152189838046636.jpg',
'https://s1.huishoubao.com/zuji/images/content/152189924187136.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152189924187136.jpg',
'https://s1.huishoubao.com/zuji/images/content/152187822549429.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152187822549429.jpg',
'https://s1.huishoubao.com/zuji/images/content/152188171176268.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152188171176268.jpg',
'https://s1.huishoubao.com/zuji/images/content/152187822255230.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152187822255230.jpg',
'https://s1.huishoubao.com/zuji/images/content/152188170782821.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152188170782821.jpg',
'https://s1.huishoubao.com/zuji/images/content/152188113950377.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152188113950377.jpg',
'https://s1.huishoubao.com/zuji/images/content/152187766753755.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152187766753755.jpg',
'https://s1.huishoubao.com/zuji/images/content/152179513114485.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152179513114485.jpg',
'https://s1.huishoubao.com/zuji/images/content/152171130973782.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152171130973782.jpg',
'https://s1.huishoubao.com/zuji/images/content/152171170280055.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152171170280055.jpg',
'https://s1.huishoubao.com/zuji/images/content/152162294885813.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152162294885813.jpg',
'https://s1.huishoubao.com/zuji/images/content/152066392727993.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152066392727993.jpg',
'https://s1.huishoubao.com/zuji/images/content/152170882099483.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152170882099483.jpg',
'https://s1.huishoubao.com/zuji/images/content/152066411556537.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152066411556537.jpg',
'https://s1.huishoubao.com/zuji/images/content/152065218893734.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152065218893734.jpg',
'https://s1.huishoubao.com/zuji/images/content/152041613865293.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152041613865293.jpg',
'https://s1.huishoubao.com/zuji/images/content/152041564294250.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152041564294250.jpg',
'https://s1.huishoubao.com/zuji/images/content/152041708160879.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152041708160879.jpg',
'https://s1.huishoubao.com/zuji/images/content/152041745979669.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152041745979669.jpg',
'https://s1.huishoubao.com/zuji/images/content/152041671285531.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152041671285531.jpg',
'https://s1.huishoubao.com/zuji/images/content/152041436347919.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152041436347919.jpg',
'https://s1.huishoubao.com/zuji/images/content/151980433093840.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151980433093840.jpg',
'https://s1.huishoubao.com/zuji/images/content/151980376243486.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151980376243486.jpg',
'https://s1.huishoubao.com/zuji/images/content/151980501659204.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151980501659204.jpg',
'https://s1.huishoubao.com/zuji/images/content/151980502170810.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151980502170810.jpg',
'https://s1.huishoubao.com/zuji/images/content/151980433021456.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151980433021456.jpg',
'https://s1.huishoubao.com/zuji/images/content/151980376225782.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151980376225782.jpg',
'https://s1.huishoubao.com/zuji/images/content/152041436328932.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152041436328932.jpg',
'https://s1.huishoubao.com/zuji/images/content/151964097833416.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151964097833416.jpg',
'https://s1.huishoubao.com/zuji/images/content/151964027792472.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151964027792472.jpg',
'https://s1.huishoubao.com/zuji/images/content/151964027750052.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151964027750052.jpg',
'https://s1.huishoubao.com/zuji/images/content/151980376212205.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151980376212205.jpg',
'https://s1.huishoubao.com/zuji/images/content/151833101783464.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151833101783464.jpg',
'https://s1.huishoubao.com/zuji/images/content/151833101794905.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151833101794905.jpg',
'https://s1.huishoubao.com/zuji/images/content/151833177752521.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151833177752521.jpg',
'https://s1.huishoubao.com/zuji/images/content/151833101710615.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151833101710615.jpg',
'https://s1.huishoubao.com/zuji/images/content/151808963226631.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151808963226631.jpg',
'https://s1.huishoubao.com/zuji/images/content/151808963260703.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151808963260703.jpg',
'https://s1.huishoubao.com/zuji/images/content/151808963271725.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151808963271725.jpg',
'https://s1.huishoubao.com/zuji/images/content/151809036196060.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151809036196060.jpg',
'https://s1.huishoubao.com/zuji/images/content/151809036183022.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151809036183022.jpg',
'https://s1.huishoubao.com/zuji/images/content/151737036693990.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151737036693990.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643860824280.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643860824280.jpg',
'https://s1.huishoubao.com/zuji/images/content/151728040967330.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151728040967330.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643860834176.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643860834176.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643860877329.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643860877329.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643670391827.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643670391827.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643401652562.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643401652562.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643519810176.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643519810176.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643519842418.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643519842418.jpg',
'https://s1.huishoubao.com/zuji/images/content/151642024588258.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151642024588258.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643362068753.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643362068753.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643047364651.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643047364651.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643047375830.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643047375830.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643047352506.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643047352506.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643362019992.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643362019992.jpg',
'https://s1.huishoubao.com/zuji/images/content/151641998929724.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151641998929724.jpg',
'https://s1.huishoubao.com/zuji/images/content/151642024517741.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151642024517741.jpg',
'https://s1.huishoubao.com/zuji/images/content/151641935137853.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151641935137853.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635529968396.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635529968396.jpg',
'https://s1.huishoubao.com/zuji/images/content/151641935114961.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151641935114961.jpg',
'https://s1.huishoubao.com/zuji/images/content/151641998912470.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151641998912470.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635529866584.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635529866584.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635529843053.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635529843053.jpg',
'https://s1.huishoubao.com/zuji/images/content/151641998920308.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151641998920308.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635307676094.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635307676094.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635020963420.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635020963420.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635102089680.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635102089680.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635020952624.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635020952624.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635097658434.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635097658434.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635020949149.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635020949149.jpg',
'https://s1.huishoubao.com/zuji/images/content/152894810911759.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152894810911759.png',
'https://s1.huishoubao.com/zuji/images/content/152393342229057.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152393342229057.png',
'https://s1.huishoubao.com/zuji/images/content/153240386880936.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153240386880936.png',
'https://s1.huishoubao.com/zuji/images/content/153086374164616.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153086374164616.png',
'https://s1.huishoubao.com/zuji/images/content/152940922733236.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152940922733236.png',
'https://s1.huishoubao.com/zuji/images/content/152895630150092.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152895630150092.png',
'https://s1.huishoubao.com/zuji/images/content/151643776336445.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643776336445.jpg',
'https://s1.huishoubao.com/zuji/images/content/152332598544566.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152332598544566.jpg',
'https://s1.huishoubao.com/zuji/images/content/152888956758786.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152888956758786.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643362074570.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643362074570.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643702847915.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643702847915.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635248130947.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635248130947.jpg',
'https://s1.huishoubao.com/zuji/images/content/151964097821359.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151964097821359.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643670389187.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643670389187.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635164328179.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635164328179.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635302285286.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635302285286.jpg',
'https://s1.huishoubao.com/zuji/images/content/152041564596431.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152041564596431.jpg',
'https://s1.huishoubao.com/zuji/images/content/151609122774852.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151609122774852.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635197678802.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635197678802.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635510017925.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635510017925.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643289149557.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643289149557.jpg',
'https://s1.huishoubao.com/zuji/images/content/152041564525815.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152041564525815.jpg',
'https://s1.huishoubao.com/zuji/images/content/151929810135318.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151929810135318.jpg',
'https://s1.huishoubao.com/zuji/images/content/152361142719517.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152361142719517.jpg',
'https://s1.huishoubao.com/zuji/images/content/152402006368663.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152402006368663.jpg',
'https://s1.huishoubao.com/zuji/images/content/152066472691263.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152066472691263.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643803394524.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643803394524.jpg',
'https://s1.huishoubao.com/zuji/images/content/152663639784026.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152663639784026.jpg',
'https://s1.huishoubao.com/zuji/images/content/151816804769693.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151816804769693.jpg',
'https://s1.huishoubao.com/zuji/images/content/151817003423260.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151817003423260.jpg',
'https://s1.huishoubao.com/zuji/images/content/151816680730923.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151816680730923.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635164135544.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635164135544.jpg',
'https://s1.huishoubao.com/zuji/images/content/151728040931822.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151728040931822.jpg',
'https://s1.huishoubao.com/zuji/images/content/152041671782491.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152041671782491.jpg',
'https://s1.huishoubao.com/zuji/images/content/152041614010313.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152041614010313.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635114958657.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635114958657.jpg',
'https://s1.huishoubao.com/zuji/images/content/152834092098972.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152834092098972.jpg',
'https://s1.huishoubao.com/zuji/images/content/152706889743073.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152706889743073.jpg',
'https://s1.huishoubao.com/zuji/images/content/152889654916159.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152889654916159.jpg',
'https://s1.huishoubao.com/zuji/images/content/152834166930915.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152834166930915.jpg',
'https://s1.huishoubao.com/zuji/images/content/152041436350578.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152041436350578.jpg',
'https://s1.huishoubao.com/zuji/images/content/151964102685900.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151964102685900.jpg',
'https://s1.huishoubao.com/zuji/images/content/151736997035772.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151736997035772.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643803292587.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643803292587.jpg',
'https://s1.huishoubao.com/zuji/images/content/152889526179790.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152889526179790.jpg',
'https://s1.huishoubao.com/zuji/images/content/152171170787789.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152171170787789.jpg',
'https://s1.huishoubao.com/zuji/images/content/152574554084132.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152574554084132.jpg',
'https://s1.huishoubao.com/zuji/images/content/152361141932997.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152361141932997.jpg',
'https://s1.huishoubao.com/zuji/images/content/151736880539237.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151736880539237.jpg',
'https://s1.huishoubao.com/zuji/images/content/152707234961839.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152707234961839.jpg',
'https://s1.huishoubao.com/zuji/images/content/152569297524098.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152569297524098.jpg',
'https://s1.huishoubao.com/zuji/images/content/152239351263662.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152239351263662.jpg',
'https://s1.huishoubao.com/zuji/images/content/151641935193634.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151641935193634.jpg',
'https://s1.huishoubao.com/zuji/images/content/151728040978807.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151728040978807.jpg',
'https://s1.huishoubao.com/zuji/images/content/151817114352821.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151817114352821.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643401622222.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643401622222.jpg',
'https://s1.huishoubao.com/zuji/images/content/152707234569441.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152707234569441.jpg',
'https://s1.huishoubao.com/zuji/images/content/151927065165370.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151927065165370.jpg',
'https://s1.huishoubao.com/zuji/images/content/151816877592017.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151816877592017.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643483733285.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643483733285.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643289174649.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643289174649.jpg',
'https://s1.huishoubao.com/zuji/images/content/152706889456986.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152706889456986.jpg',
'https://s1.huishoubao.com/zuji/images/content/151443230883639.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151443230883639.jpg',
'https://s1.huishoubao.com/zuji/images/content/152569605268118.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152569605268118.jpg',
'https://s1.huishoubao.com/zuji/images/content/152834133063757.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152834133063757.jpg',
'https://s1.huishoubao.com/zuji/images/content/152187767580349.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152187767580349.jpg',
'https://s1.huishoubao.com/zuji/images/content/152430628969773.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152430628969773.jpg',
'https://s1.huishoubao.com/zuji/images/content/152655299730656.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152655299730656.jpg',
'https://s1.huishoubao.com/zuji/images/content/152440380455428.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152440380455428.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643497143995.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643497143995.jpg',
'https://s1.huishoubao.com/zuji/images/content/152707235133316.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152707235133316.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635430349214.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635430349214.jpg',
'https://s1.huishoubao.com/zuji/images/content/151737036577442.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151737036577442.jpg',
'https://s1.huishoubao.com/zuji/images/content/153215790944790.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153215790944790.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635197599650.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635197599650.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643828429663.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643828429663.jpg',
'https://s1.huishoubao.com/zuji/images/content/152188170892831.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152188170892831.jpg',
'https://s1.huishoubao.com/zuji/images/content/151816680763603.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151816680763603.jpg',
'https://s1.huishoubao.com/zuji/images/content/153190355355360.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153190355355360.jpg',
'https://s1.huishoubao.com/zuji/images/content/152655295211899.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152655295211899.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643776363488.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643776363488.jpg',
'https://s1.huishoubao.com/zuji/images/content/152187766934707.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152187766934707.jpg',
'https://s1.huishoubao.com/zuji/images/content/152402006635297.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152402006635297.jpg',
'https://s1.huishoubao.com/zuji/images/content/153092705797200.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153092705797200.jpg',
'https://s1.huishoubao.com/zuji/images/content/151632886448939.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151632886448939.jpg',
'https://s1.huishoubao.com/zuji/images/content/153389525171296.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153389525171296.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643828477722.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643828477722.jpg',
'https://s1.huishoubao.com/zuji/images/content/151737068877869.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151737068877869.jpg',
'https://s1.huishoubao.com/zuji/images/content/152187767358792.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152187767358792.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643803277541.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643803277541.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635510055633.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635510055633.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643289110168.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643289110168.jpg',
'https://s1.huishoubao.com/zuji/images/content/152189874891942.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152189874891942.jpg',
'https://s1.huishoubao.com/zuji/images/content/151444262843303.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151444262843303.jpg',
'https://s1.huishoubao.com/zuji/images/content/152569296717555.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152569296717555.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643702882144.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643702882144.jpg',
'https://s1.huishoubao.com/zuji/images/content/151737111061534.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151737111061534.jpg',
'https://s1.huishoubao.com/zuji/images/content/151444263027906.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151444263027906.jpg',
'https://s1.huishoubao.com/zuji/images/content/152189838228989.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152189838228989.jpg',
'https://s1.huishoubao.com/zuji/images/content/151512458613169.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151512458613169.jpg',
'https://s1.huishoubao.com/zuji/images/content/152170884988966.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152170884988966.jpg',
'https://s1.huishoubao.com/zuji/images/content/151833177744141.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151833177744141.jpg',
'https://s1.huishoubao.com/zuji/images/content/152361141754124.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152361141754124.jpg',
'https://s1.huishoubao.com/zuji/images/content/152689178413292.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152689178413292.jpg',
'https://s1.huishoubao.com/zuji/images/content/152586016398584.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152586016398584.jpg',
'https://s1.huishoubao.com/zuji/images/content/152041708227632.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152041708227632.jpg',
'https://s1.huishoubao.com/zuji/images/content/151738413183367.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151738413183367.jpg',
'https://s1.huishoubao.com/zuji/images/content/152188208587923.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152188208587923.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643519894614.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643519894614.jpg',
'https://s1.huishoubao.com/zuji/images/content/151816877598262.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151816877598262.jpg',
'https://s1.huishoubao.com/zuji/images/content/153389461246457.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153389461246457.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635197670716.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635197670716.jpg',
'https://s1.huishoubao.com/zuji/images/content/151737111080441.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151737111080441.jpg',
'https://s1.huishoubao.com/zuji/images/content/151609122356651.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151609122356651.jpg',
'https://s1.huishoubao.com/zuji/images/content/153190355070898.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153190355070898.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643483741250.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643483741250.jpg',
'https://s1.huishoubao.com/zuji/images/content/152663640019479.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152663640019479.jpg',
'https://s1.huishoubao.com/zuji/images/content/153215954063557.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153215954063557.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643670336486.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643670336486.jpg',
'https://s1.huishoubao.com/zuji/images/content/152222217756467.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152222217756467.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635510026898.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635510026898.jpg',
'https://s1.huishoubao.com/zuji/images/content/152179512973338.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152179512973338.jpg',
'https://s1.huishoubao.com/zuji/images/content/152222217242608.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152222217242608.jpg',
'https://s1.huishoubao.com/zuji/images/content/151817115538459.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151817115538459.jpg',
'https://s1.huishoubao.com/zuji/images/content/151964102622445.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151964102622445.jpg',
'https://s1.huishoubao.com/zuji/images/content/153215791576715.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153215791576715.jpg',
'https://s1.huishoubao.com/zuji/images/content/151737036597715.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151737036597715.jpg',
'https://s1.huishoubao.com/zuji/images/content/151738413179490.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151738413179490.jpg',
'https://s1.huishoubao.com/zuji/images/content/153215972492531.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153215972492531.jpg',
'https://s1.huishoubao.com/zuji/images/content/152361142273114.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152361142273114.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635430328366.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635430328366.jpg',
'https://s1.huishoubao.com/zuji/images/content/152066411437370.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152066411437370.jpg',
'https://s1.huishoubao.com/zuji/images/content/153215712323817.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153215712323817.jpg',
'https://s1.huishoubao.com/zuji/images/content/152569298318467.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152569298318467.jpg',
'https://s1.huishoubao.com/zuji/images/content/151816804769669.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151816804769669.jpg',
'https://s1.huishoubao.com/zuji/images/content/151736880527051.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151736880527051.jpg',
'https://s1.huishoubao.com/zuji/images/content/151927065192070.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151927065192070.jpg',
'https://s1.huishoubao.com/zuji/images/content/152585983545067.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152585983545067.jpg',
'https://s1.huishoubao.com/zuji/images/content/152187822786017.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152187822786017.jpg',
'https://s1.huishoubao.com/zuji/images/content/151632886113981.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151632886113981.jpg',
'https://s1.huishoubao.com/zuji/images/content/152362007048391.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152362007048391.jpg',
'https://s1.huishoubao.com/zuji/images/content/152655270651227.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152655270651227.jpg',
'https://s1.huishoubao.com/zuji/images/content/151444263383909.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151444263383909.jpg',
'https://s1.huishoubao.com/zuji/images/content/151980502426385.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151980502426385.jpg',
'https://s1.huishoubao.com/zuji/images/content/152171030435921.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152171030435921.jpg',
'https://s1.huishoubao.com/zuji/images/content/151816680743595.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151816680743595.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643401615399.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643401615399.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635248157001.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635248157001.jpg',
'https://s1.huishoubao.com/zuji/images/content/151929810178826.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151929810178826.jpg',
'https://s1.huishoubao.com/zuji/images/content/152361141522242.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152361141522242.jpg',
'https://s1.huishoubao.com/zuji/images/content/151980433029513.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151980433029513.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643828472195.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643828472195.jpg',
'https://s1.huishoubao.com/zuji/images/content/151643776362887.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151643776362887.jpg',
'https://s1.huishoubao.com/zuji/images/content/151642024573699.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151642024573699.jpg',
'https://s1.huishoubao.com/zuji/images/content/152332598235829.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152332598235829.jpg',
'https://s1.huishoubao.com/zuji/images/content/152868926736282.png'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152868926736282.png',
'https://s1.huishoubao.com/zuji/images/content/151832978967387.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151832978967387.jpg',
'https://s1.huishoubao.com/zuji/images/content/152171131310530.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152171131310530.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635248163172.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635248163172.jpg',
'https://s1.huishoubao.com/zuji/images/content/151736997016608.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151736997016608.jpg',
'https://s1.huishoubao.com/zuji/images/content/151443231124616.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151443231124616.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635302888046.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635302888046.jpg',
'https://s1.huishoubao.com/zuji/images/content/153199582250360.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153199582250360.jpg',
'https://s1.huishoubao.com/zuji/images/content/152188114089306.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152188114089306.jpg',
'https://s1.huishoubao.com/zuji/images/content/153215711932237.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153215711932237.jpg',
'https://s1.huishoubao.com/zuji/images/content/153388713312377.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153388713312377.jpg',
'https://s1.huishoubao.com/zuji/images/content/151512458913666.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151512458913666.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635430329265.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635430329265.jpg',
'https://s1.huishoubao.com/zuji/images/content/151980433070897.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151980433070897.jpg',
'https://s1.huishoubao.com/zuji/images/content/153389208677908.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/153389208677908.jpg',
'https://s1.huishoubao.com/zuji/images/content/152895720949798.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152895720949798.jpg',
'https://s1.huishoubao.com/zuji/images/content/152188263447821.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152188263447821.jpg',
'https://s1.huishoubao.com/zuji/images/content/152957782286036.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152957782286036.jpg',
'https://s1.huishoubao.com/zuji/images/content/151964097882420.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151964097882420.jpg',
'https://s1.huishoubao.com/zuji/images/content/152655274614804.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152655274614804.jpg',
'https://s1.huishoubao.com/zuji/images/content/151736880538981.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151736880538981.jpg',
'https://s1.huishoubao.com/zuji/images/content/152957735885091.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152957735885091.jpg',
'https://s1.huishoubao.com/zuji/images/content/152440373472330.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152440373472330.jpg',
'https://s1.huishoubao.com/zuji/images/content/152171030090742.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152171030090742.jpg',
'https://s1.huishoubao.com/zuji/images/content/151609166390233.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151609166390233.jpg',
'https://s1.huishoubao.com/zuji/images/content/152889115122055.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152889115122055.jpg',
'https://s1.huishoubao.com/zuji/images/content/152041746247981.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152041746247981.jpg',
'https://s1.huishoubao.com/zuji/images/content/152440368585871.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152440368585871.jpg',
'https://s1.huishoubao.com/zuji/images/content/152188263588688.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/152188263588688.jpg',
'https://s1.huishoubao.com/zuji/images/content/151635164131706.jpg'=>'https://cdn-static-files.nqyong.com/zuji/images/content/151635164131706.jpg',
];

echo '开始时间:'.date('Y-m-d H:i:s',$t).';___';
//数据库配置
$user = 'root';//用户名
$password = '123456';//密码
$dbname1 = 'zuji';//老数据库库名
$dbname2 = 'zuji_order';//新库名
$dbname3 = 'zuji_warehouse';//新收发货库名
$host = '127.0.0.1';//host
$port = 3306;//端口

//数据库1 (老)
$db1=new mysqli($host,$user,$password,$dbname1,$port);
if(mysqli_connect_error()){
    echo 'Could not connect to database 1.';
    exit;
}
mysqli_query($db1,'set names utf8');

//关闭自动提交
$db1->autocommit(false);

//DB1 数据查询
// 查询商品
$result_spu_all1=$db1->query("SELECT id,imgs,thumb,content FROM zuji_goods_spu");

while($arr = $result_spu_all1->fetch_assoc()){
    //商品二维数组
    $spu_all[]=$arr;
}
$sel++;

//------------------修改商品图片信息------------------
if( !spuImgs($spu_all,$db1,$str_l,$str_x,$str_all) ){
    $db1->rollback();//回滚
    //关闭链接
    $db1->close();
    echo '修改商品图片信息失败';
    die;

}

//提交
$db1->commit();
$db1->autocommit(TRUE);
$db1->close();

$tn = time();
echo '结束时间:'.date('Y-m-d H:i:s',$tn).';___';
$etime=microtime(true);
echo '用时:'.($tn-$t).'(秒);___';
echo '查询总条数:'.$sel.';___';
echo '插入总条数:'.$num.';___';

die;


//===方法体=====================================================================================================================

/**
 * 导入商品图片信息
 */
function spuImgs($spu_all,$db1,$str_l,$str_x,$str_all){
    global $num;
    echo '修改商品图片信息开始 '.date('Y-m-d H:i:s',time()).'___';

    foreach ($spu_all as $key=>$item) {
        $sup_sql = "UPDATE zuji_goods_spu SET ";
        $_img = false;
        $_thumb = false;
        $_content = false;
        if($item['imgs']){
            $imgs_all = json_decode($item['imgs'],true);
            foreach ($imgs_all as $k=>$value){
//                if(strstr($value,$str_l)){
//                    //存在
//                    $imgs_all[$k] = str_replace($str_l, $str_x, $value);
//                    $_img = true;
//                }
                if($str_all[$value]){
                    //存在
                    $imgs_all[$k] = $str_all[$value];
                    $_img = true;
                }
            }
        }
        if($item['thumb']){
//            if(strstr($item['thumb'],$str_l)){
//                //存在
//                $spu_all[$key]['thumb'] = str_replace($str_l, $str_x, $item['thumb']);
//                $_thumb = true;
//            }
            if($str_all[$item['thumb']]){
                //存在
                $spu_all[$key]['thumb'] = $str_all[$item['thumb']];
                $_thumb = true;
            }
        }
        if($item['content']){
            foreach ($str_all as $k=>$v){
                if(strstr($item['content'],$k)){
                    //存在
                    $spu_all[$key]['content'] = str_replace($k, $v, $item['content']);
                    $_content = true;
                }
            }
//            if(strstr($item['content'],$str_l)){
//                //存在
//                $spu_all[$key]['content'] = str_replace($str_l, $str_x, $item['content']);
//                $_content = true;
//            }
        }

        if(!$_img && !$_thumb && !$_content){
            continue;
        }

        if($_img){
            $sup_sql .= "imgs='".json_encode($imgs_all)."',";
        }
        if($_thumb){
            $sup_sql .= "thumb='".$spu_all[$key]['thumb']."',";
        }
        if($_content){
            $sup_sql .= "content='".$spu_all[$key]['content']."',";
        }
        $sup_sql = substr($sup_sql,0,-1);

        $sup_sql .= " WHERE id = '".$item['id']."'";
        if($db1->query($sup_sql)){
            //echo '导入商品图片信息表成功;___';
            $num++;
        }else{
            echo '修改商品图片信息表失败;___';
            echo $sup_sql;
            return false;
        }

    }
    return true;

}

