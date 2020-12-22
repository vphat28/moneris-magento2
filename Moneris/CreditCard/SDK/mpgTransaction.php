<?php

namespace Moneris\CreditCard\SDK;


##################### mpgTransaction ########################################

class mpgTransaction
{

	var $txn;
	var $custInfo = null;
	var $recur = null;
	var $cvd = null;
	var $cof = null;
	var $avs = null;
	var $convFee = null;
	var $ach = null;
	var $sessionAccountInfo = null;
	var $attributeAccountInfo = null;
	var $level23Data = null;
	var $mcpRateInfo = null;

	public function __construct($txn)
	{
		$this->txn=$txn;
	}

	public function getCustInfo()
	{
		return $this->custInfo;
	}

	public function setCustInfo($custInfo)
	{
		$this->custInfo = $custInfo;
		array_push($this->txn,$custInfo);
	}

	public function getRecur()
	{
		return $this->recur;
	}

	public function setRecur($recur)
	{
		$this->recur = $recur;
	}

	public function getTransaction()
	{
		return $this->txn;
	}

	public function getCvdInfo()
	{
		return $this->cvd;
	}

	public function setCvdInfo($cvd)
	{
		$this->cvd = $cvd;
	}

	public function getAvsInfo()
	{
		return $this->avs;
	}

	public function setAvsInfo($avs)
	{
		$this->avs = $avs;
	}

	public function getCofInfo()
	{
		return $this->cof;
	}

	public function setCofInfo($cof)
	{
		$this->cof = $cof;
	}

	public function getMCPRateInfo()
	{
		return $this->mcpRateInfo;
	}

	public function setMCPRateInfo($mcpRate)
	{
		$this->mcpRateInfo = $mcpRate;
	}

	public function getAchInfo()
	{
		return $this->ach;
	}

	public function setAchInfo($ach)
	{
		$this->ach = $ach;
	}

	public function setConvFeeInfo($convFee)
	{
		$this->convFee = $convFee;
	}

	public function getConvFeeInfo()
	{
		return $this->convFee;
	}

	public function setExpiryDate($expdate)
	{
		$this->expdate = $expdate;
	}

	public function getExpiryDate()
	{
		return $this->expdate;
	}

	public function getAttributeAccountInfo()
	{
		return $this->attributeAccountInfo;
	}

	public function setAttributeAccountInfo($attributeAccountInfo)
	{
		$this->attributeAccountInfo = $attributeAccountInfo;
	}

	public function getSessionAccountInfo()
	{
		return $this->sessionAccountInfo;
	}

	public function setSessionAccountInfo($sessionAccountInfo)
	{
		$this->sessionAccountInfo = $sessionAccountInfo;
	}

	public function setLevel23Data($level23Object)
	{
		$this->level23Data = $level23Object;
	}

	public function getLevel23Data()
	{
		return $this->level23Data;
	}

}//end class mpgTransaction