<?php


namespace Moneris\CreditCard\SDK;

class MCPRate
{
	private $template = array (
		"merchant_settlement_amount" => null,
		"cardholder_amount" => null,
		"cardholder_currency_code" => null,
	);

	private $data;
	private $mcp_rate;

	public function __construct()
	{
		$this->mcp_rate = array();
	}

	public function setMerchantSettlementAmount($merchant_settlement_amount, $cardholder_currency_code)
	{
		$this->data = $this->template;
		$this->data['merchant_settlement_amount'] = $merchant_settlement_amount;
		$this->data['cardholder_currency_code'] = $cardholder_currency_code;

		array_push($this->mcp_rate, $this->data);
	}

	public function setCardholderAmount($cardholder_amount, $cardholder_currency_code)
	{
		$this->data = $this->template;
		$this->data['cardholder_amount'] = $cardholder_amount;
		$this->data['cardholder_currency_code'] = $cardholder_currency_code;

		array_push($this->mcp_rate, $this->data);
	}


	public function toXML()
	{
		$final_data['rate'] = $this->mcp_rate;

		$xmlString = $this->toXML_low($final_data, "rate");

		return $xmlString;
		//return "<rate>". $xmlString. "</rate>";
	}

	private function toXML_low($dataArray, $root)
	{
		$xmlRoot = "";

		foreach ($dataArray as $key => $value)
		{
			if(!is_numeric($key) && $value != "" && $value != null)
			{
				$xmlRoot .= "<$key>";
			}
			else if(is_numeric($key) && $key != "0")
			{
				$xmlRoot .= "</$root><$root>";
			}

			if(is_array($value))
			{
				$xmlRoot .= $this->toXML_low($value, $key);
			}
			else
			{
				$xmlRoot .= $value;
			}

			if(!is_numeric($key) && $value != "" && $value != null)
			{
				$xmlRoot .= "</$key>";
			}
		}

		return $xmlRoot;
	}
}