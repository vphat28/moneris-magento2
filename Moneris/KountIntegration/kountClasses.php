<?php

#################### kountGlobals ###########################################


class kountGlobals{

    var $Globals=array(
        'MONERIS_PROTOCOL' => 'https',
        'MONERIS_HOST' => 'www3.moneris.com', //default
        'MONERIS_TEST_HOST' => 'esqa.moneris.com',
        'MONERIS_US_HOST' => 'esplus.moneris.com',
        'MONERIS_US_TEST_HOST' => 'esplusqa.moneris.com',
        'MONERIS_PORT' =>'443',
        'MONERIS_FILE' => '/gateway2/servlet/MpgRequest',
        'API_VERSION' =>'PHP - 1.2.0 - KOUNT',
        'CLIENT_TIMEOUT' => '60'
    );

    function __construct()
    {
        // default
    }

    function getGlobals()
    {
        return($this->Globals);
    }

}//end class kountGlobals



###################### kountHttpsPost #########################################

class kountHttpsPost{

    var $api_token;
    var $store_id;
    var $kountRequest;
    var $kountResponse;

    function __construct($storeid,$apitoken,$kountRequestOBJ,$testMode)
    {

        $this->store_id=$storeid;
        $this->api_token= $apitoken;
        $this->kountRequest=$kountRequestOBJ;
        $dataToSend=$this->toXML();

        //echo "DATA TO SEND: $dataToSend\n";
        //do post

        $g=new kountGlobals();
        $gArray=$g->getGlobals();

        if ($testMode) {
            $url = $gArray['MONERIS_PROTOCOL'] . "://" .
                $gArray['MONERIS_TEST_HOST'] . ":" .
                $gArray['MONERIS_PORT'] .
                $gArray['MONERIS_FILE'];
        } else {
            $url = $gArray['MONERIS_PROTOCOL'] . "://" .
                $gArray['MONERIS_HOST'] . ":" .
                $gArray['MONERIS_PORT'] .
                $gArray['MONERIS_FILE'];
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt ($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$dataToSend);
        curl_setopt($ch,CURLOPT_TIMEOUT,$gArray['CLIENT_TIMEOUT']);
        curl_setopt($ch,CURLOPT_USERAGENT,$gArray['API_VERSION']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);

        $response=curl_exec ($ch);

        //echo "RESPONSE: $response\n";

        curl_close ($ch);

        if(!$response)
        {

            $response="<?xml version=\"1.0\"?><response><receipt>".
                "<ReceiptId>Global Error Receipt</ReceiptId>".
                "<ResponseCode>null</ResponseCode>".
                "<AuthCode>null</AuthCode><TransTime>null</TransTime>".
                "<TransDate>null</TransDate><TransType>null</TransType><Complete>false</Complete>".
                "<Message>null</Message><TransAmount>null</TransAmount>".
                "<CardType>null</CardType>".
                "<TransID>null</TransID><TimedOut>null</TimedOut>".
                "</receipt></response>";
        }

        //print "Got a xml response of: \n$response\n";
        $this->kountResponse=new kountResponse($response);

    }



    function getkountResponse()
    {
        return $this->kountResponse;

    }

    function toXML( )
    {
        $xmlString = '';
        $req=$this->kountRequest ;
        $reqXMLString=$req->toXML();

        $xmlString .="<?xml version=\"1.0\"?>".
            "<request>".
            "<store_id>$this->store_id</store_id>".
            "<api_token>$this->api_token</api_token>".
            $reqXMLString.
            "</request>";

        return ($xmlString);

    }

}//end class kountHttpsPost



############# kountResponse #####################################################


class kountResponse{

    var $responseData;

    var $p; //parser

    var $currentTag;
    var $isKountInfo;
    var $kountInfo = array();

    function __construct($xmlString)
    {

        $this->p = xml_parser_create();
        xml_parser_set_option($this->p,XML_OPTION_CASE_FOLDING,0);
        xml_parser_set_option($this->p,XML_OPTION_TARGET_ENCODING,"UTF-8");
        xml_set_object($this->p,$this);
        xml_set_element_handler($this->p,"startHandler","endHandler");
        xml_set_character_data_handler($this->p,"characterHandler");
        xml_parse($this->p,$xmlString);
        xml_parser_free($this->p);

    }//end of constructor


    function getkountResponse()
    {
        return($this->responseData);
    }

    //-----------------  Receipt Variables  ---------------------------------------------------------//

    function getReceiptId()
    {
        return ($this->responseData['ReceiptId']);
    }

    function getResponseCode()
    {
        return ($this->responseData['ResponseCode']);
    }

    function getMessage()
    {
        return ($this->responseData['Message']);
    }

    function getKountInfo()
    {
        return ($this->kountInfo);
    }

    function getKountResult()
    {
        return ($this->responseData['KountResult']);
    }

    function getKountScore()
    {
        return ($this->responseData['KountScore']);
    }

//-----------------  Parser Handlers  ---------------------------------------------------------//

    function characterHandler($parser,$data)
    {
        @$this->responseData[$this->currentTag] .=$data;

        if($this->isKountInfo)
        {
            //print("\n".$this->currentTag."=".$data);
            $this->kountInfo[$this->currentTag] = $data;

        }
    }//end characterHandler



    function startHandler($parser,$name,$attrs)
    {
        $this->currentTag=$name;

        if($this->currentTag == "KountInfo")
        {
            $this->isKountInfo=1;
        }
    } //end startHandler

    function endHandler($parser,$name)
    {
        $this->currentTag=$name;

        if($name == "KountInfo")
        {
            $this->isKountInfo=0;
        }

        $this->currentTag="/dev/null";
    } //end endHandler



}//end class kountResponse


################## kountRequest ###########################################################

class kountRequest{

    var $txnTypes =array(
        'kount_inquiry' => array('kount_merchant_id','kount_api_key','order_id','call_center_ind','currency','data_key','email','customer_id', 'auto_number_id','financial_order_id','payment_token','payment_type','ip_address','session_id','website_id','amount','payment_response','avs_response','cvd_response','bill_street_1','bill_street_2','bill_country','bill_city','bill_postal_code','bill_phone','bill_province','dob','epoc','gender','last4','customer_name','ship_street_1', 'ship_street_2', 'ship_country', 'ship_city', 'ship_email', 'ship_name', 'ship_postal_code', 'ship_phone', 'ship_province', 'ship_type','udf'),
        'kount_update' => array('kount_merchant_id','kount_api_key','order_id','session_id', 'kount_transaction_id', 'evaluate', 'refund_status','payment_response', 'avs_response', 'cvd_response','last4', 'financial_order_id', 'payment_token', 'payment_type')
    );

    var $txnArray;

    function __construct($txn){

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

    function toXML()
    {
        $xmlString = '';
        $tmpTxnArray=$this->txnArray;

        $txnArrayLen=count($tmpTxnArray); //total number of transactions
        for($x=0;$x < $txnArrayLen;$x++)
        {
            $txnObj=$tmpTxnArray[$x];
            $txn=$txnObj->getTransaction();

            $txnType = $txn['type'];
            $tmpTxnTypes = $this->txnTypes;
            $txnTypeArray=$tmpTxnTypes[$txnType];
            $txnTypeArrayLen=count($txnTypeArray); //length of a specific txn type

            $nkeys=array_values(array_diff(array_keys($txn),array_values($txnTypeArray))); // find all the ,'prod_type_n','prod_item_n','prod_desc_n','prod_quant_n','prod_price_n','local_attrib_n'

            $txnXMLString="";
            for($i=0;$i < $txnTypeArrayLen ;$i++)
            {
                if (isset($txn[$txnTypeArray[$i]]) && strlen($txn[$txnTypeArray[$i]]) > 0 )
                {
                    $txnXMLString  .="<$txnTypeArray[$i]>"   //begin tag
                        .$txn[$txnTypeArray[$i]] // data
                        . "</$txnTypeArray[$i]>"; //end tag
                }
            }

            //'prod_type_n','prod_item_n','prod_desc_n','prod_quant_n','prod_price_n','local_attrib_n' part
            for ( $i=0 ; $i<count($nkeys) ; $i++ )
            {
                if ( preg_match("/(prod_type_|prod_item_|prod_desc_|prod_quant_|prod_price_)\d+/i", $nkeys[$i]) && strlen($txn[$nkeys[$i]]) > 0 )
                {
                    $txnXMLString  .="<$nkeys[$i]>"   //begin tag
                        .$txn[$nkeys[$i]] // data
                        . "</$nkeys[$i]>"; //end tag
                }
            }

            $txnXMLString = "<$txnType>$txnXMLString";

            $txnXMLString .="</$txnType>";

            $xmlString .=$txnXMLString;

            return $xmlString;


        }

        return $xmlString;

    }//end toXML



}//end class

##################### kountTransaction #######################################################

class kountTransaction{

    var $txn;
    var $attributeAccountInfo = null;
    var $sessionAccountInfo = null;

    function __construct($txn)
    {
        $this->txn=$txn;
    }

    function getTransaction()
    {
        return $this->txn;
    }

}//end class

