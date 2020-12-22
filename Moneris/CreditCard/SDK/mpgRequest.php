<?php

namespace Moneris\CreditCard\SDK;



################## mpgRequest ###############################################

class mpgRequest
{

	var $txnTypes =array(
		//Basic
		'batchclose' => array('ecr_number'),
		'card_verification' =>array('order_id','cust_id','pan','expdate', 'crypt_type'),
		'cavv_preauth' =>array('order_id','cust_id', 'amount', 'pan','expdate', 'cavv','crypt_type','dynamic_descriptor', 'wallet_indicator', 'cm_id'),
		'cavv_purchase' => array('order_id','cust_id', 'amount', 'pan','expdate', 'cavv','crypt_type', 'dynamic_descriptor', 'network', 'data_type','wallet_indicator', 'cm_id'),
		'completion' => array('order_id', 'comp_amount','txn_number', 'crypt_type', 'cust_id', 'dynamic_descriptor', 'ship_indicator'),
		'contactless_purchase' => array('order_id','cust_id','amount','track2','pan','expdate', 'pos_code','dynamic_descriptor'),
		'contactless_purchasecorrection' => array('order_id','txn_number'),
		'contactless_refund' => array('order_id','amount','txn_number'),
		'forcepost'=> array('order_id','cust_id','amount','pan','expdate','auth_code','crypt_type','dynamic_descriptor'),
		'ind_refund' => array('order_id','cust_id', 'amount','pan','expdate', 'crypt_type','dynamic_descriptor'),
		'opentotals' => array('ecr_number'),
		'preauth' =>array('order_id','cust_id', 'amount', 'pan', 'expdate', 'crypt_type','dynamic_descriptor', 'wallet_indicator', 'market_indicator', 'cm_id'),
		'purchase'=> array('order_id','cust_id', 'amount', 'pan', 'expdate', 'crypt_type','dynamic_descriptor', 'wallet_indicator', 'market_indicator', 'cm_id'),
		'purchasecorrection' => array('order_id', 'txn_number', 'crypt_type', 'cust_id', 'dynamic_descriptor'),
		'reauth' =>array('order_id','cust_id', 'amount', 'orig_order_id', 'txn_number', 'crypt_type', 'dynamic_descriptor'),
		'recur_update' => array('order_id','cust_id','pan','expdate','recur_amount','add_num_recurs','total_num_recurs','hold','terminate'),
		'refund' => array('order_id', 'amount', 'txn_number', 'crypt_type', 'cust_id', 'dynamic_descriptor'),

		//Encrypted
		'enc_card_verification' => array('order_id','cust_id','enc_track2','device_type', 'crypt_type'),
		'enc_forcepost' => array('order_id','cust_id','amount','enc_track2','device_type','auth_code','crypt_type','dynamic_descriptor'),
		'enc_ind_refund' => array('order_id','cust_id','amount','enc_track2','device_type','crypt_type','dynamic_descriptor'),
		'enc_preauth' => array('order_id','cust_id','amount','enc_track2','device_type','crypt_type','dynamic_descriptor'),
		'enc_purchase' => array('order_id','cust_id','amount','enc_track2','device_type','crypt_type','dynamic_descriptor'),
		'enc_res_add_cc' => array('cust_id','phone','email','note','enc_track2','device_type','crypt_type', 'data_key_format'),
		'enc_res_update_cc' => array('data_key','cust_id','phone','email','note','enc_track2','device_type','crypt_type'),
		'enc_track2_forcepost' => array('order_id','cust_id','amount','enc_track2','pos_code','device_type','auth_code','dynamic_descriptor'),
		'enc_track2_ind_refund' => array('order_id','cust_id','amount','enc_track2','pos_code','device_type','dynamic_descriptor'),
		'enc_track2_preauth' => array('order_id','cust_id','amount','enc_track2','pos_code','device_type','dynamic_descriptor'),
		'enc_track2_purchase' => array('order_id','cust_id','amount','enc_track2','pos_code','device_type','dynamic_descriptor'),

		//Interac Online
		'idebit_purchase' =>array('order_id', 'cust_id', 'amount','idebit_track2','dynamic_descriptor'),
		'idebit_refund' =>array('order_id','amount','txn_number'),

		//Vault
		'res_add_cc' => array('cust_id','phone','email','note','pan','expdate','crypt_type', 'data_key_format'),
		'res_add_token' => array('data_key','cust_id','phone','email','note','expdate','crypt_type', 'data_key_format'),
		'res_card_verification_cc' => array('data_key','order_id', 'crypt_type', 'expdate'),
		'res_cavv_preauth_cc' => array('data_key','order_id','cust_id','amount','cavv','crypt_type','dynamic_descriptor','expdate'),
		'res_cavv_purchase_cc' => array('data_key','order_id','cust_id','amount','cavv','crypt_type','dynamic_descriptor','expdate'),
		'res_delete' => array('data_key'),
		'res_get_expiring' => array(),
		'res_ind_refund_cc' => array('data_key','order_id','cust_id','amount','crypt_type','dynamic_descriptor'),
		'res_iscorporatecard' => array('data_key'),
		'res_lookup_full' => array('data_key'),
		'res_lookup_masked' => array('data_key'),
		'res_mpitxn' => array('data_key','xid','amount','MD','merchantUrl','accept','userAgent','expdate'),
		'res_preauth_cc' => array('data_key','order_id','cust_id','amount','crypt_type','dynamic_descriptor','expdate', 'market_indicator'),
		'res_purchase_cc' => array('data_key','order_id','cust_id','amount','crypt_type','dynamic_descriptor','expdate', 'market_indicator'),
		'res_temp_add' => array('pan','expdate','crypt_type','duration', 'data_key_format', 'anc1'),
		'res_temp_tokenize' => array('order_id', 'txn_number', 'duration', 'crypt_type'),
		'res_tokenize_cc' => array('order_id','txn_number','cust_id','phone','email','note', 'data_key_format'),
		'res_update_cc' => array('data_key','cust_id','phone','email','note','pan','expdate','crypt_type'),
		'res_forcepost_cc' => array('order_id','cust_id','amount','data_key','auth_code', 'crypt_type','dynamic_descriptor'),

		//Track2
		'track2_completion' => array('order_id', 'comp_amount','txn_number','pos_code','dynamic_descriptor'),
		'track2_forcepost'=>array('order_id','cust_id', 'amount', 'track2','pan','expdate','pos_code','auth_code','dynamic_descriptor'),
		'track2_ind_refund' => array('order_id','amount','track2','pan','expdate','cust_id','pos_code','dynamic_descriptor'),
		'track2_preauth' => array('order_id','cust_id','amount','track2','pan','expdate','pos_code','dynamic_descriptor'),
		'track2_purchase' =>array('order_id','cust_id','amount','track2','pan','expdate','pos_code','dynamic_descriptor'),
		'track2_purchasecorrection' => array('order_id', 'txn_number'),
		'track2_refund' => array('order_id', 'amount', 'txn_number','dynamic_descriptor'),

		//VDotMe
		'vdotme_completion' => array('order_id','comp_amount','txn_number','crypt_type','cust_id','dynamic_descriptor'),
		'vdotme_getpaymentinfo' => array('callid'),
		'vdotme_preauth' => array('order_id','amount','callid','crypt_type','cust_id','dynamic_descriptor'),
		'vdotme_purchase' => array('order_id','amount','callid','crypt_type','cust_id','dynamic_descriptor'),
		'vdotme_purchasecorrection' => array('order_id','txn_number','crypt_type','cust_id','dynamic_descriptor'),
		'vdotme_reauth' => array('order_id','orig_order_id','txn_number','amount','crypt_type','cust_id','dynamic_descriptor'),
		'vdotme_refund' => array('order_id','txn_number','amount','crypt_type','cust_id','dynamic_descriptor'),

		//MasterPass
		'paypass_send_shopping_cart' => array('subtotal', 'suppress_shipping_address'),
		'paypass_retrieve_checkout_data' => array('oauth_token', 'oauth_verifier', 'checkout_resource_url'),
		'paypass_purchase' => array('order_id', 'cust_id', 'amount', 'mp_request_token', 'crypt_type', 'dynamic_descriptor'),
		'paypass_cavv_purchase' => array('order_id', 'cavv', 'cust_id', 'amount', 'mp_request_token', 'crypt_type', 'dynamic_descriptor'),
		'paypass_preauth' => array('order_id', 'cust_id', 'amount', 'mp_request_token', 'crypt_type', 'dynamic_descriptor'),
		'paypass_cavv_preauth' => array('order_id', 'cavv', 'cust_id', 'amount', 'mp_request_token', 'crypt_type', 'dynamic_descriptor'),
		'paypass_txn' => array('xid', 'amount', 'mp_request_token', 'MD', 'merchantUrl', 'accept', 'userAgent'),

		//US ACH
		'us_ach_credit' => array('order_id','cust_id','amount'),
		'us_ach_debit' => array('order_id','cust_id','amount'),
		'us_ach_fi_enquiry' => array('routing_num'),
		'us_ach_reversal' => array('order_id','txn_number'),

		//US Basic
		'us_batchclose' => array('ecr_number'),
		'us_card_verification' => array('order_id','cust_id','pan','expdate'),
		'us_cavv_preauth' => array('order_id','cust_id', 'amount', 'pan','expdate', 'cavv','crypt_type','dynamic_descriptor', 'wallet_indicator'),
		'us_cavv_purchase'=> array('order_id','cust_id','amount','pan','expdate', 'cavv', 'commcard_invoice','commcard_tax_amount','crypt_type', 'dynamic_descriptor', 'wallet_indicator'),
		'us_completion' => array('order_id', 'comp_amount','txn_number', 'crypt_type', 'commcard_invoice','commcard_tax_amount', 'ship_indicator'),
		'us_contactless_purchase' => array('order_id','cust_id','amount','track2','pan','expdate','commcard_invoice','commcard_tax_amount','pos_code','dynamic_descriptor'),
		'us_contactless_purchasecorrection' => array('order_id','txn_number'),
		'us_contactless_refund' => array('order_id','amount','txn_number'),
		'us_forcepost'=> array('order_id','cust_id','amount','pan','expdate','auth_code','crypt_type','dynamic_descriptor'),
		'us_ind_refund' => array('order_id','cust_id', 'amount','pan','expdate', 'crypt_type','dynamic_descriptor'),
		'us_opentotals' => array('ecr_number'),
		'us_pinless_debit_purchase' => array('order_id','amount','pan','expdate','cust_id','presentation_type','intended_use','p_account_number'),
		'us_pinless_debit_refund' => array('order_id', 'amount', 'txn_number'),
		'us_preauth' => array('order_id','cust_id', 'amount', 'pan', 'expdate', 'crypt_type', 'dynamic_descriptor'),
		'us_purchase'=> array('order_id','cust_id', 'amount', 'pan', 'expdate', 'crypt_type', 'commcard_invoice','commcard_tax_amount','dynamic_descriptor'),
		'us_purchasecorrection' => array('order_id', 'txn_number', 'crypt_type'),
		'us_reauth' => array('order_id','cust_id','orig_order_id','txn_number','amount','crypt_type'),
		'us_recur_update' => array('order_id', 'cust_id', 'pan', 'expdate', 'recur_amount','add_num_recurs', 'total_num_recurs', 'hold', 'terminate','avs_street_number', 'avs_street_name', 'avs_zipcode'),
		'us_refund' => array('order_id', 'amount', 'txn_number', 'crypt_type'),

		//US Encrypted
		'us_enc_card_verification' => array('order_id','cust_id','enc_track2','device_type'),
		'us_enc_forcepost' => array('order_id','cust_id','amount','enc_track2','device_type','auth_code','crypt_type','dynamic_descriptor'),
		'us_enc_ind_refund' => array('order_id','cust_id','amount','enc_track2','device_type','crypt_type','dynamic_descriptor'),
		'us_enc_preauth' => array('order_id','cust_id','amount','enc_track2','device_type','crypt_type','dynamic_descriptor'),
		'us_enc_purchase' => array('order_id','cust_id','amount','enc_track2','device_type','crypt_type','commcard_invoice','commcard_tax_amount','dynamic_descriptor'),
		'us_enc_res_add_cc' => array('cust_id','phone','email','note','enc_track2','device_type','crypt_type', 'data_key_format'),
		'us_enc_res_update_cc' => array('data_key','cust_id','phone','email','note','enc_track2','device_type','crypt_type'),
		'us_enc_track2_forcepost' => array('order_id','cust_id','amount','enc_track2','pos_code','device_type','auth_code','dynamic_descriptor'),
		'us_enc_track2_ind_refund' => array('order_id','cust_id','amount','enc_track2','pos_code','device_type','dynamic_descriptor'),
		'us_enc_track2_preauth' => array('order_id','cust_id','amount','enc_track2','pos_code','device_type','dynamic_descriptor'),
		'us_enc_track2_purchase' => array('order_id','cust_id','amount','enc_track2','pos_code','device_type','commcard_invoice','commcard_tax_amount','dynamic_descriptor'),

		//US Vault
		'us_res_add_cc' => array('cust_id','phone','email','note','pan','expdate','crypt_type', 'data_key_format'),
		'us_res_add_ach' => array('cust_id','phone','email','note'),
		'us_res_add_pinless' => array('cust_id','phone','email','note','pan','expdate','presentation_type','p_account_number'),
		'us_res_add_token' => array('cust_id','phone','email','note','data_key','crypt_type','expdate', 'data_key_format'),
		'us_res_delete' => array('data_key'),
		'us_res_get_expiring' => array(),
		'us_res_ind_refund_ach' => array('data_key','order_id','cust_id','amount'),
		'us_res_ind_refund_cc' => array('data_key','order_id','cust_id','amount','crypt_type','dynamic_descriptor'),
		'us_res_iscorporatecard' => array('data_key'),
		'us_res_lookup_full' => array('data_key'),
		'us_res_lookup_masked' => array('data_key'),
		'us_res_preauth_cc' => array('data_key','order_id','cust_id','amount','crypt_type','dynamic_descriptor'),
		'us_res_purchase_ach' => array('data_key','order_id','cust_id','amount'),
		'us_res_purchase_cc' => array('data_key','order_id','cust_id','amount','crypt_type','commcard_invoice','commcard_tax_amount','dynamic_descriptor'),
		'us_res_purchase_pinless' => array('data_key','order_id','cust_id','amount','intended_use','p_account_number'),
		'us_res_temp_add' => array('pan','expdate','duration','crypt_type', 'data_key_format'),
		'us_res_tokenize_cc' => array('order_id','txn_number','cust_id','phone','email','note', 'data_key_format'),
		'us_res_update_cc' => array('data_key','cust_id','phone','email','note','pan','expdate','crypt_type'),
		'us_res_update_ach' => array('data_key','cust_id','phone','email','note'),
		'us_res_update_pinless' => array('data_key','cust_id','phone','email','note','pan','expdate','presentation_type','p_account_number'),

		//US Track2
		'us_track2_completion' => array('order_id', 'comp_amount','txn_number','pos_code', 'commcard_invoice','commcard_tax_amount'),
		'us_track2_forcepost'=>array('order_id','cust_id', 'amount', 'track2','pan','expdate','pos_code','auth_code','dynamic_descriptor'),
		'us_track2_ind_refund' => array('order_id','amount','track2','pan','expdate','cust_id','pos_code','dynamic_descriptor'),
		'us_track2_preauth' => array('order_id','cust_id','amount','track2','pan','expdate','pos_code','dynamic_descriptor'),
		'us_track2_purchase' =>array('order_id','cust_id','amount','track2','pan','expdate', 'commcard_invoice','commcard_tax_amount','pos_code','dynamic_descriptor'),
		'us_track2_purchasecorrection' => array('order_id', 'txn_number'),
		'us_track2_refund' => array('order_id', 'amount', 'txn_number'),

		//MPI - Common CA and US
		'txn' =>array('xid', 'amount', 'pan', 'expdate','MD', 'merchantUrl','accept','userAgent','currency','recurFreq', 'recurEnd','install'),
		'acs'=> array('PaRes','MD'),

		//Group Transaction - Common CA and US
		'group'=> array('order_id', 'txn_number', 'group_ref_num', 'group_type'),

		//Risk - CA only
		'session_query' => array('order_id','session_id','service_type','event_type'),
		'attribute_query' => array('order_id','policy_id','service_type'),

		//Level 23
		'iscorporatecard' => array('pan','expdate'),

		//Amex General level23
		'axcompletion' => array('order_id', 'comp_amount', 'txn_number', 'crypt_type'),
		'axrefund' => array('order_id', 'amount', 'txn_number', 'crypt_type'),
		'axind_refund' => array('order_id', 'cust_id', 'amount', 'pan', 'expdate', 'crypt_type'),
		'axpurchasecorrection' => array('order_id', 'txn_number', 'crypt_type'),
		'axforcepost' => array('order_id', 'cust_id', 'amount', 'pan', 'expdate', 'auth_code', 'crypt_type'),

		//Amex Air & Rail level23
		'axracompletion' => array('order_id', 'comp_amount', 'txn_number', 'crypt_type'),
		'axrarefund' => array('order_id', 'amount', 'txn_number', 'crypt_type'),
		'axraind_refund' => array('order_id', 'cust_id', 'amount', 'pan', 'expdate', 'crypt_type'),
		'axrapurchasecorrection' => array('order_id', 'txn_number', 'crypt_type'),
		'axraforcepost' => array('order_id', 'cust_id', 'amount', 'pan', 'expdate', 'auth_code', 'crypt_type'),

		//Visa General, Air & Rail Level23
		'vscompletion' => array('order_id', 'comp_amount', 'txn_number', 'crypt_type', 'national_tax', 'merchant_vat_no', 'local_tax', 'customer_vat_no', 'cri', 'customer_code', 'invoice_number', 'local_tax_no'),
		'vsrefund' => array('order_id', 'amount', 'txn_number', 'crypt_type', 'national_tax', 'merchant_vat_no', 'local_tax', 'customer_vat_no', 'cri','customer_code', 'invoice_number', 'local_tax_no'),
		'vsind_refund' => array('order_id', 'cust_id', 'amount', 'pan', 'expdate', 'crypt_type', 'national_tax', 'merchant_vat_no', 'local_tax', 'customer_vat_no', 'cri','customer_code', 'invoice_number', 'local_tax_no'),
		'vsforcepost' => array('order_id', 'cust_id', 'amount', 'pan', 'expdate', 'auth_code', 'crypt_type', 'national_tax', 'merchant_vat_no', 'local_tax', 'customer_vat_no', 'cri','customer_code', 'invoice_number', 'local_tax_no'),
		'vspurchasecorrection' => array('order_id', 'txn_number', 'crypt_type'),
		'vscorpais' => array('order_id', 'txn_number'),

		//MasterCard General, Air and Rail Level23
		'mccompletion' => array('order_id', 'comp_amount', 'txn_number', 'merchant_ref_no', 'crypt_type'),
		'mcrefund' => array('order_id', 'amount', 'txn_number', 'merchant_ref_no', 'crypt_type'),
		'mcind_refund' => array('order_id', 'cust_id', 'amount', 'pan', 'expdate', 'merchant_ref_no', 'crypt_type'),
		'mcpurchasecorrection' => array('order_id', 'txn_number', 'crypt_type'),
		'mcforcepost' => array('order_id', 'cust_id', 'amount', 'pan', 'expdate', 'auth_code', 'merchant_ref_no', 'crypt_type'),
		'mccorpais' => array('order_id', 'txn_number'),

		//MCP transactions
		'mcp_completion' => array('order_id','txn_number', 'crypt_type', 'cust_id', 'dynamic_descriptor', 'ship_indicator', 'mcp_version', 'cardholder_amount', 'cardholder_currency_code', 'mcp_rate_token'),
		'mcp_ind_refund' => array('order_id','cust_id','pan','expdate', 'crypt_type','dynamic_descriptor', 'mcp_version', 'cardholder_amount', 'cardholder_currency_code', 'mcp_rate_token'),
		'mcp_preauth' =>array('order_id','cust_id', 'pan', 'expdate', 'crypt_type','dynamic_descriptor', 'wallet_indicator', 'market_indicator', 'cm_id', 'mcp_version', 'cardholder_amount', 'cardholder_currency_code', 'mcp_rate_token'),
		'mcp_purchase'=> array('order_id','cust_id', 'pan', 'expdate', 'crypt_type','dynamic_descriptor', 'wallet_indicator', 'market_indicator', 'cm_id', 'mcp_version', 'cardholder_amount', 'cardholder_currency_code', 'mcp_rate_token'),
		'mcp_purchasecorrection' => array('order_id', 'txn_number', 'crypt_type', 'cust_id', 'dynamic_descriptor'),
		'mcp_refund' => array('order_id', 'amount', 'txn_number', 'crypt_type', 'cust_id', 'dynamic_descriptor', 'mcp_version', 'cardholder_amount', 'cardholder_currency_code', 'mcp_rate_token'),
		'mcp_res_ind_refund_cc' => array('data_key','order_id','cust_id','crypt_type','dynamic_descriptor', 'mcp_version', 'cardholder_amount', 'cardholder_currency_code', 'mcp_rate_token'),
		'mcp_res_preauth_cc' => array('data_key','order_id','cust_id','crypt_type','dynamic_descriptor','expdate', 'mcp_version', 'cardholder_amount', 'cardholder_currency_code', 'mcp_rate_token'),
		'mcp_res_purchase_cc' => array('data_key','order_id','cust_id','crypt_type','dynamic_descriptor','expdate', 'mcp_version', 'cardholder_amount', 'cardholder_currency_code', 'mcp_rate_token'),
		'mcp_get_rate' => array('mcp_version', 'rate_txn_type')
	);

	var $txnArray;
	var $procCountryCode = "";
	var $testMode = "";
	var $isMPI = "";

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

	public function getIsMPI()
	{
		$txnType = $this->getTransactionType();

		if((strcmp($txnType, "txn") === 0) || (strcmp($txnType, "acs") === 0))
		{
			//$this->setIsMPI(true);
			return true;
		}
		else
		{
			return false;
		}
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

	public function getTransactionType()
	{
		$jtmp=$this->txnArray;
		$jtmp1=$jtmp[0]->getTransaction();
		$jtmp2=array_shift($jtmp1);
		return $jtmp2;
	}

	public function getURL()
	{
		$g=new mpgGlobals();
		$gArray=$g->getGlobals();

		$txnType = $this->getTransactionType();

		if(strpos($txnType, "us_") !== false)
		{
			$this->setProcCountryCode("US");
		}

		if((strcmp($txnType, "txn") === 0) || (strcmp($txnType, "acs") === 0))
		{
			$this->isMPI = "_MPI";
		}
		else
		{
			$this->isMPI = "";
		}

		$hostId = "MONERIS".$this->procCountryCode.$this->testMode."_HOST";
		$fileId = "MONERIS".$this->procCountryCode.$this->isMPI."_FILE";

		$url =  $gArray['MONERIS_PROTOCOL']."://".
		        $gArray[$hostId].":".
		        $gArray['MONERIS_PORT'].
		        $gArray[$fileId];

		return $url;
	}

	var $xmlString;
	public function toXML()
	{
		$tmpTxnArray=$this->txnArray;
		$txnArrayLen=count($tmpTxnArray); //total number of transactions

		for($x=0;$x < $txnArrayLen;$x++)
		{
			$txnObj=$tmpTxnArray[$x];
			$txn=$txnObj->getTransaction();

			$txnType=array_shift($txn);
			if (($this->procCountryCode === "_US") && (strpos($txnType, "us_") !== 0))
			{
				if((strcmp($txnType, "txn") === 0) || (strcmp($txnType, "acs") === 0) || (strcmp($txnType, "group") === 0))
				{
					//do nothing
				}
				else
				{
					$txnType = "us_".$txnType;
				}
			}
			$tmpTxnTypes=$this->txnTypes;
			$txnTypeArray=$tmpTxnTypes[$txnType];
			$txnTypeArrayLen=count($txnTypeArray); //length of a specific txn type

			$txnXMLString="";

			//for risk transactions only
			if((strcmp($txnType, "attribute_query") === 0) || (strcmp($txnType, "session_query") === 0))
			{
				$txnXMLString .="<risk>";
			}

			$txnXMLString .="<$txnType>";

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

			$recur  = $txnObj->getRecur();
			if($recur != null)
			{
				$txnXMLString .= $recur->toXML();
			}

			$avs  = $txnObj->getAvsInfo();
			if($avs != null)
			{
				$txnXMLString .= $avs->toXML();
			}

			$cvd  = $txnObj->getCvdInfo();
			if($cvd != null)
			{
				$txnXMLString .= $cvd->toXML();
			}

			$cof = $txnObj->getCofInfo();
			if($cof != null)
			{
				$txnXMLString .= $cof->toXML();
			}

			$custInfo = $txnObj->getCustInfo();
			if($custInfo != null)
			{
				$txnXMLString .= $custInfo->toXML();
			}

			$ach = $txnObj->getAchInfo();
			if($ach != null)
			{
				$txnXMLString .= $ach->toXML();
			}

			$convFee  = $txnObj->getConvFeeInfo();
			if($convFee != null)
			{
				$txnXMLString .= $convFee->toXML();
			}

			$sessionQuery  = $txnObj->getSessionAccountInfo();
			if($sessionQuery != null)
			{
				$txnXMLString .= $sessionQuery->toXML();
			}

			$attributeQuery  = $txnObj->getAttributeAccountInfo();
			if($attributeQuery != null)
			{
				$txnXMLString .= $attributeQuery->toXML();
			}

			$level23Data = $txnObj->getLevel23Data();
			if($level23Data != null)
			{
				$txnXMLString .= $level23Data->toXML();
			}

			$mcpRateInfo = $txnObj->getMCPRateInfo();
			if($mcpRateInfo != null && $txnType == 'mcp_get_rate')
			{
				$txnXMLString .= "<rate_info>".$mcpRateInfo->toXML()."</rate_info>";
			}

			$txnXMLString .="</$txnType>";

			//for risk transactions only
			if((strcmp($txnType, "attribute_query") === 0) || (strcmp($txnType, "session_query") === 0))
			{
				$txnXMLString .="</risk>";
			}

			$this->xmlString .=$txnXMLString;

		}
		return $this->xmlString;

	}//end toXML



}//end class