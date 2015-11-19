<?php
/**
 * 取一个二维数组中的每个数组的固定的键知道的值来形成一个新的一维数组
 * @param $pArray 一个二维数组
 * @param $pKey 数组的键的名称
 * @return 返回新的一维数组
 */
function getSubByKey($pArray, $pKey="", $pCondition=""){
    $result = array();
    if(is_array($pArray)){
        foreach($pArray as $temp_array){
            if(is_object($temp_array)){
                $temp_array = (array) $temp_array;
            }
            if((""!=$pCondition && $temp_array[$pCondition[0]]==$pCondition[1]) || ""==$pCondition) {
                $result[] = (""==$pKey) ? $temp_array : isset($temp_array[$pKey]) ? $temp_array[$pKey] : "";
            }
        }
        return $result;
    }else{
        return false;
    }
}

/**
 * 根据经纬度计算距离 
 * @param  [type] $lat1 用户a的纬度
 * @param  [type] $lng1 用户a的经度
 * @param  [type] $lat2 用户b的纬度
 * @param  [type] $lng2 用户b的经度
 * @return [type]       距离(米)
 */
function getDistance($lat1, $lng1, $lat2, $lng2) {
    //地球半径
    $R = 6378137;
    //将角度转为狐度
    $radLat1 = deg2rad($lat1);
    $radLat2 = deg2rad($lat2);
    $radLng1 = deg2rad($lng1);
    $radLng2 = deg2rad($lng2);
    //结果
    $s = acos(cos($radLat1)*cos($radLat2)*cos($radLng1-$radLng2)+sin($radLat1)*sin($radLat2))*$R;
    //精度
    $s = round($s* 10000)/10000;
    return  round($s);
}
?>