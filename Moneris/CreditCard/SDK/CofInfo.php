<?php


namespace Moneris\CreditCard\SDK;

class CofInfo
{
	private $template = array(
		'payment_indicator' => null,
		'payment_information' => null,
		'issuer_id' => null);

	private $data;

	public function __construct()
	{
		$this->data = $this->template;
	}

	public function setPaymentIndicator($payment_indicator)
	{
		$this->data['payment_indicator'] = $payment_indicator;
	}

	public function setPaymentInformation($payment_information)
	{
		$this->data['payment_information'] = $payment_information;
	}

	public function setIssuerId($issuer_id)
	{
		$this->data['issuer_id'] = $issuer_id;
	}

	public function toXML()
	{
		$xmlString = "";

		foreach($this->template as $key=>$value)
		{
			if($this->data[$key] != null || $this->data[$key] != "")
			{
				$xmlString .= "<$key>". $this->data[$key] ."</$key>";
			}
		}

		return "<cof_info>$xmlString</cof_info>";
	}

}//end class
