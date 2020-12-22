<?php

namespace Moneris\CreditCard\SDK;

class axIt106s
{
	private $template = array (
		'it10618' => null, 'it10719' => null
	);

	private $data;

	public function __construct()
	{
		$this->data = $this->template;
	}

	public function setIt10618($it10618)
	{
		$this->data['it10618'] = $it10618;
	}

	public function setIt10719($it10719)
	{
		$this->data['it10719'] = $it10719;
	}

	public function getData()
	{
		return $this->data;
	}
}