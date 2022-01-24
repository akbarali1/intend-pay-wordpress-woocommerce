<?php

namespace Intend;
/**
 * Created by PhpStorm.
 * Filename: intend.php
 * Project Name: wordpress.loc
 * User: Akbarali
 * Date: 17/12/2021
 * Time: 12:23 PM
 * Github: https://github.com/akbarali1
 * Telegram: @kbarali
 * E-mail: akbarali@webschool.uz
 */
class Intend
{

    public function calculate($post_data)
    {
        $ch = curl_init();
        $url = "https://pay.intend.uz/api/v1/front/calculate-all";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);                //0 for a get request
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $response_json = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response_json, true);
        foreach ($response['data']['items'] as $product_curl) {

            $array_ichki[] = [
                'duration' => $product_curl['prices']['0']['duration'],
                'price' => number_format($product_curl['prices']['0']['price'], 0, ',', ' '),
                'per_month' => number_format($product_curl['prices']['0']['per_month'], 0, ',', ' '),
            ];
            $oylar = [];
            foreach ($product_curl['prices'] as $price) {
                $oylar[] = $price['duration'];
            }

            $return_price[] = [
                'id' => $product_curl['id'],
                'original_price' => number_format($product_curl['original_price'], 0, ',', ' '),
                'prices' => $array_ichki,
            ];
            $array_ichki = [];
        }
        $return_price['months'] = $oylar;

        return $return_price;

    }

    public function orderCheck($order_id, $api_key): bool
    {
        if (!is_numeric($order_id)) {
            return false;
        }
        $ch = curl_init();
        $url = "https://pay.intend.uz/api/v1/external/order/check";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);                //0 for a get request
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['order_id' => $order_id]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            'api-key: ' . $api_key,
        ));
        $response_json = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response_json, true);

        if ($response['success'] === true) {
            return true;
        } else {
            return false;
        }

    }


}