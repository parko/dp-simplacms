<?php

$order = $simpla->orders->get_order(intval($_REQUEST['order_id']));

if(empty($order))
	return;
 
$method = $simpla->payment->get_payment_method(intval($order->payment_method_id));

if(empty($method))
	return;

$settings = unserialize($method->settings);
$payment_currency = $simpla->money->get_currency(intval($method->currency_id));

$amount = round($simpla->money->convert($order->total_price, $method->currency_id, false), 2)*100;

    $request = array(
      'checkout' => array(
        'version' => 2,
	    "transaction_type"=> "authorization",

		'settings' => array(
      "success_url"=> $_SERVER["HTTP_REFERER"],
      "decline_url"=> $_SERVER["HTTP_REFERER"],
      "fail_url"=> $_SERVER["HTTP_REFERER"],
      "cancel_url"=> $_SERVER["HTTP_REFERER"],
      "notification_url"=> 'http://'.$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"],
      "language"=> "en",
          'customer_fields' => array(
            'read_only' => array('email'),
            'hidden' => array("phone"),
          ),
        ),
        'order' => array(
      "currency"=> $payment_currency->code,
      "amount"=> $amount,
      "description"=> "Order ".$order->id." payment"
        ),
		'customer' => array(
      "address"=> $order->address,
      "name"=> $order->name,
      "phone"=> $order->phone,
      "email"=> $order->email,
        ),

		
		),
      );



	$request=(object)$request;
	$request->checkout=(object)($request->checkout);
	$request->checkout->order=(object)($request->checkout->order);
	$request->checkout->settings=(object)($request->checkout->settings);
	$request->checkout->settings->customer_fields=(object)($request->checkout->settings->customer_fields);
	$request->checkout->customer=(object)($request->checkout->customer);

	$shop_id=$settings['delta_id'];
	$shop_key=$settings['delta_secret'];

	
	$host='https://checkout.deltaprocessing.ru/ctp/api/checkouts';

   $response = d_submit($shop_id, $shop_key, $host, $request);
   $response = json_decode($response);


	if($response->checkout->redirect_url){
		header('Location: '.$response->checkout->redirect_url);
	}

    function d_submit($shop_id, $shop_key, $host, $t_request) {

        $process = curl_init($host);
        $json = json_encode($t_request);

        if (!empty($t_request)) {
          curl_setopt($process, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-type: application/json'));
          curl_setopt($process, CURLOPT_POST, 1);
          curl_setopt($process, CURLOPT_POSTFIELDS, $json);
        }
        curl_setopt($process, CURLOPT_URL, $host);
        curl_setopt($process, CURLOPT_USERPWD, $shop_id . ":" . $shop_key);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($process);
        curl_close($process);

        return $response;
    }
