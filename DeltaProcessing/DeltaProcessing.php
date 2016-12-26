<?php

require_once('api/Simpla.php');

class DeltaProcessing extends Simpla
{

	public function checkout_form($order_id, $button_text = null)
	{
		if(empty($button_text))
			$button_text = 'Перейти к оплате';

		$button = '<form method="POST" action="payment/DeltaProcessing/callback.php">
					<input type="hidden" name="delta_send" value="7"/>
					<input type="hidden" name="order_id" value="'.$order_id.'"/>
					<input type="submit" name="submit-button" value="'.$button_text.'"  class="checkout_button">
					</form>
					';
		
		return $button;
	}
}