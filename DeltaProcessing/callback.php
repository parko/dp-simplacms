<?php
/*
 */


// Работаем в корневой директории
chdir ('../../');
require_once('api/Simpla.php');
$simpla = new Simpla();



if($_POST['delta_send']){
include 'delta_send.php';
return;
}

	if($_SERVER["CONTENT_TYPE"] ==  'application/json'){
		$postData = file_get_contents('php://input');
		$data = json_decode($postData, true);
	}


////////////////////////////////////////////////
// Проверка статуса
////////////////////////////////////////////////
if($data['transaction']['status'] !== 'successful')
	err('bad status');

if(!preg_match('@([0-9]+)@', $data['transaction']['description'], $mt)){
	err('bad description');
}
	$order_id=$mt[1];

////////////////////////////////////////////////
// Выберем заказ из базы
////////////////////////////////////////////////
$order = $simpla->orders->get_order(intval($order_id));
if(empty($order))
	err('Заказ не найден');
 
////////////////////////////////////////////////
// Выбираем из базы соответствующий метод оплаты
////////////////////////////////////////////////
$method = $simpla->payment->get_payment_method(intval($order->payment_method_id));
if(empty($method))
	err("Неизвестный метод оплаты");
	
//$settings = unserialize($method->settings);
$payment_currency = $simpla->money->get_currency(intval($method->currency_id));

// Нельзя оплатить уже оплаченный заказ  
if($order->paid)
	err('Этот заказ уже оплачен');


$amount = round($simpla->money->convert($order->total_price, $method->currency_id, false), 2)*100;


if($data['transaction']['amount'] != $amount || $amount<=0)
	err("incorrect price");

// Установим статус оплачен
$simpla->orders->update_order(intval($order->id), array('paid'=>1));

// Отправим уведомление на email
$simpla->notify->email_order_user(intval($order->id));
$simpla->notify->email_order_admin(intval($order->id));

// Спишем товары  
$simpla->orders->close(intval($order->id));

function err($msg)
{
	header($_SERVER['SERVER_PROTOCOL'].' 400 Bad Request', true, 400);
	// mail("test@test", "yandex: $msg", $msg);
	die($msg);
}