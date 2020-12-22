<?php

namespace Moneris\CreditCard\SDK;


################## mpiRequest ###############################################

class MpiRequest
{

	var $txnTypes = array(
		'txn' =>array('xid', 'amount', 'pan', 'expdate','MD', 'merchantUrl','accept','userAgent','currency','recurFreq', 'recurEnd','install'),
		'acs'=> array('PaRes','MD')
	);

	var $txnArray;
	var $procCountryCode = "";
	var $testMode = "";

	public function __construct($txn)
	{

		if(is_array($txn))
		{
			$this->txnArray = $txn;
		}
		else
		{
			$temp[0]=$txn;
			$this->txnArray=$temp;
		}
	}
	public function setProcCountryCode($countryCode)
	{
		$this->procCountryCode = ((strcmp(strtolower($countryCode), "us") >= 0) ? "_US" : "");
	}

	public function setTestMode($state)
	{
		if($state === true)
		{
			$this->testMode = "_TEST";
		}
		else
		{
			$this->testMode = "";
		}
	}

	public function getURL()
	{
		$g=new mpgGlobals();
		$gArray=$g->getGlobals();

		//$txnType = $this->getTransactionType();

		$hostId = "MONERIS".$this->procCountryCode.$this->testMode."_HOST";
		$fileId = "MONERIS".$this->procCountryCode."_MPI_FILE";

		$url =  $gArray['MONERIS_PROTOCOL']."://".
		        $gArray[$hostId].":".
		        $gArray['MONERIS_PORT'].
		        $gArray[$fileId];

		//echo "PostURL: " . $url;

		return $url;
	}

	public function toXML()
	{
		$xmlString = "";
		$tmpTxnArray=$this->txnArray;
		$txnArrayLen=count($tmpTxnArray); //total number of transactions

		for($x=0;$x < $txnArrayLen;$x++)
		{
			$txnObj=$tmpTxnArray[$x];
			$txn=$txnObj->getTransaction();

			$txnType=array_shift($txn);
			$tmpTxnTypes=$this->txnTypes;
			$txnTypeArray=$tmpTxnTypes[$txnType];
			$txnTypeArrayLen=count($txnTypeArray); //length of a specific txn type

			$txnXMLString="";

			for($i=0;$i < $txnTypeArrayLen ;$i++)
			{
				//Will only add to the XML if the tag was passed in by merchant
				if(array_key_exists($txnTypeArray[$i], $txn))
				{
					$txnXMLString  .="<$txnTypeArray[$i]>"   //begin tag
					                 .$txn[$txnTypeArray[$i]] // data
					                 . "</$txnTypeArray[$i]>"; //end tag
				}
			}

			$txnXMLString = "<$txnType>$txnXMLString";

			$txnXMLString .="</$txnType>";

			$xmlString .=$txnXMLString;
		}

		return $xmlString;

	}//end toXML

}//end class MpiRequest