<?php

/*
|---------------------------------------------------
| LOAD KHUYẾN MÃI ĐANG HOẠT ĐỘNG
|---------------------------------------------------
*/

function getActivePromotions($db)
{
    $now = date('Y-m-d H:i:s');

    $stmt = $db->prepare("
        SELECT *
        FROM khuyenmai
        WHERE trang_thai = 1

        AND (
            ngay_bat_dau IS NULL
            OR ngay_bat_dau <= ?
        )

        AND (
            ngay_ket_thuc IS NULL
            OR ngay_ket_thuc >= ?
        )

        ORDER BY gia_tri DESC, id DESC
    ");

    $stmt->execute([
        $now,
        $now
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/*
|---------------------------------------------------
| CHECK KHUYẾN MÃI CÓ ÁP DỤNG KHÔNG
|---------------------------------------------------
|
| $product format:
|
| [
|   'id' => 1,
|   'danh_muc_id' => 2,
|   'gia_ban' => 120000
| ]
|
|---------------------------------------------------
*/

function isPromotionMatched($promo, $product)
{
    /*
    |---------------------------------------------------
    | CHECK ĐƠN TỐI THIỂU
    |---------------------------------------------------
    */

    if(
        isset($promo['don_hang_toi_thieu'])
        &&
        $promo['don_hang_toi_thieu'] > 0
        &&
        $product['gia_ban'] < $promo['don_hang_toi_thieu']
    ){

        return false;
    }

    /*
    |---------------------------------------------------
    | ÁP DỤNG TẤT CẢ
    |---------------------------------------------------
    */

    if($promo['ap_dung_cho'] == 'tat_ca'){

        return true;
    }

    /*
    |---------------------------------------------------
    | THEO SẢN PHẨM
    |---------------------------------------------------
    */

    if($promo['ap_dung_cho'] == 'san_pham'){

        $ids = array_filter(
            explode(',', $promo['danh_sach_ap_dung'])
        );

        return in_array($product['id'], $ids);
    }

    /*
    |---------------------------------------------------
    | THEO DANH MỤC
    |---------------------------------------------------
    */

    if($promo['ap_dung_cho'] == 'danh_muc'){

        $ids = array_filter(
            explode(',', $promo['danh_sach_ap_dung'])
        );

        return in_array($product['danh_muc_id'], $ids);
    }

    return false;
}

/*
|---------------------------------------------------
| LẤY KHUYẾN MÃI TỐT NHẤT CHO SẢN PHẨM
|---------------------------------------------------
*/

function getProductPromotion($db, $product)
{
    $promotions = getActivePromotions($db);

    $best_promo = null;

    $best_percent = 0;

    foreach($promotions as $promo){

        /*
        |---------------------------------------------------
        | CHỈ XỬ LÝ GIẢM %
        |---------------------------------------------------
        */

        if($promo['loai_khuyen_mai'] != 'giam_phan_tram'){

            continue;
        }

        /*
        |---------------------------------------------------
        | CHECK MATCH
        |---------------------------------------------------
        */

        if(!isPromotionMatched($promo, $product)){

            continue;
        }

        /*
        |---------------------------------------------------
        | ƯU TIÊN % CAO NHẤT
        |---------------------------------------------------
        */

        $percent = floatval($promo['gia_tri']);

        if($percent > $best_percent){

            $best_percent = $percent;

            $best_promo = $promo;
        }
    }

    return [

        'promotion' => $best_promo,

        'discount_percent' => $best_percent
    ];
}

/*
|---------------------------------------------------
| TÍNH KHUYẾN MÃI GIỎ HÀNG
|---------------------------------------------------
*/

function calculatePromotion($db, $cart_items, $subtotal)
{
    /*
    |---------------------------------------------------
    | LOAD CONFIG
    |---------------------------------------------------
    */

    $config_stmt = $db->query("
        SELECT ten_cau_hinh, gia_tri
        FROM cauhinh
    ");

    $configs = [];

    foreach(
        $config_stmt->fetchAll(PDO::FETCH_ASSOC)
        as $cfg
    ){

        $configs[$cfg['ten_cau_hinh']]
            = $cfg['gia_tri'];
    }

    $shipping_fee = floatval(
        $configs['shipping_fee'] ?? 25000
    );

    $free_shipping_limit = floatval(
        $configs['free_shipping_limit'] ?? 200000
    );

    /*
    |---------------------------------------------------
    | DEFAULT
    |---------------------------------------------------
    */

    $discount_amount = 0;

    $discount_name = [];

    $free_ship = false;

    /*
    |---------------------------------------------------
    | TÍNH THEO TỪNG ITEM
    |---------------------------------------------------
    */

    foreach($cart_items as $item){

        $price_data = getFinalProductPrice($db, $item);

        $item_price = $price_data['final_price'];

        $item_total =
            $item_price * $item['so_luong'];

        /*
        |---------------------------------------------------
        | GIẢM BAO NHIÊU
        |---------------------------------------------------
        */

        $item_discount =
            (
                $item['gia_ban']
                -
                $item_price
            )
            *
            $item['so_luong'];

        $discount_amount += $item_discount;

        /*
        |---------------------------------------------------
        | TÊN KHUYẾN MÃI
        |---------------------------------------------------
        */

        if(
            !empty($price_data['promotion_name'])
        ){

            $discount_name[] =
                $price_data['promotion_name'];
        }
    }

    /*
    |---------------------------------------------------
    | CHECK FREE SHIP
    |---------------------------------------------------
    */

    $promotions = getActivePromotions($db);

    foreach($promotions as $promo){

        if(
            $promo['loai_khuyen_mai']
            == 'free_ship'
        ){

            $free_ship = true;

            $discount_name[] =
                $promo['ten_khuyen_mai'];

            break;
        }
    }

    /*
    |---------------------------------------------------
    | PHÍ SHIP
    |---------------------------------------------------
    */

    if($free_ship){

        $final_shipping_fee = 0;

    } else {

        if($subtotal >= $free_shipping_limit){

            $final_shipping_fee = 0;

        } else {

            $final_shipping_fee = $shipping_fee;
        }
    }

    /*
    |---------------------------------------------------
    | FINAL TOTAL
    |---------------------------------------------------
    */

    $final_total =
        $subtotal
        -
        $discount_amount
        +
        $final_shipping_fee;

    if($final_total < 0){

        $final_total = 0;
    }

    return [

        'subtotal' => round($subtotal),

        'discount_amount' => round($discount_amount),

        'discount_name' => implode(
            ', ',
            array_unique($discount_name)
        ),

        'shipping_fee' => round($final_shipping_fee),

        'final_total' => round($final_total),

        'free_ship' => $free_ship
    ];
}
/*
|---------------------------------------------------
| TÍNH GIÁ KHUYẾN MÃI CHO 1 SẢN PHẨM
|---------------------------------------------------
*/

function getFinalProductPrice($db, $product)
{
    /*
    |---------------------------------------------------
    | GIÁ GỐC
    |---------------------------------------------------
    */

    $original_price = floatval($product['gia_ban']);

    /*
    |---------------------------------------------------
    | GIÁ ĐANG BÁN
    |---------------------------------------------------
    */

    $base_price = $original_price;

    $base_discount_percent = 0;

    /*
    |---------------------------------------------------
    | NẾU CÓ GIÁ KHUYẾN MÃI RIÊNG
    |---------------------------------------------------
    */

    if(
        !empty($product['gia_khuyen_mai'])
        &&
        $product['gia_khuyen_mai'] > 0
        &&
        $product['gia_khuyen_mai'] < $original_price
    ){

        $base_price = floatval(
            $product['gia_khuyen_mai']
        );

        $base_discount_percent =
            (
                ($original_price - $base_price)
                / $original_price
            ) * 100;
    }

    /*
    |---------------------------------------------------
    | KHUYẾN MÃI REALTIME
    |---------------------------------------------------
    */

    $promo_data = getProductPromotion($db, $product);

    $promo_percent = floatval(
        $promo_data['discount_percent']
    );

    /*
    |---------------------------------------------------
    | GIÁ SAU KHUYẾN MÃI REALTIME
    |---------------------------------------------------
    */

    $promo_price =
        $original_price
        -
        (
            $original_price
            * $promo_percent / 100
        );

    /*
    |---------------------------------------------------
    | MẶC ĐỊNH LẤY GIÁ ĐANG BÁN
    |---------------------------------------------------
    */

    $final_price = $base_price;

    $final_percent = $base_discount_percent;

    $promotion_name = '';

    /*
    |---------------------------------------------------
    | NẾU KHUYẾN MÃI REALTIME TỐT HƠN
    |---------------------------------------------------
    */

    if(
        $promo_percent > 0
        &&
        $promo_price < $base_price
    ){

        $final_price = $promo_price;

        $final_percent = $promo_percent;

        $promotion_name =
            $promo_data['promotion']['ten_khuyen_mai']
            ?? '';
    }

    return [

        'original_price' => round($original_price),

        'final_price' => round($final_price),

        'discount_percent' => round($final_percent),

        'promotion_name' => $promotion_name
    ];
}