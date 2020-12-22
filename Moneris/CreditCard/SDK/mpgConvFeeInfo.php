<?php

namespace Moneris\CreditCard\SDK;


##################### mpgConvFeeInfo ########################################

class mpgConvFeeInfo
{

	var $params;
	var $convFeeTemplate = array('convenience_fee');

	public function __construct($params)
	{
		$this->params = $params;
	}

	public function toXML()
	{
		$xmlString = "";

		foreach($this->convFeeTemplate as $tag)
		{
			$xmlString .= "<$tag>". $this->params[$tag] ."</$tag>";
		}

		return "<convfee_info>$xmlString</convfee_info>";
	}

}//end class