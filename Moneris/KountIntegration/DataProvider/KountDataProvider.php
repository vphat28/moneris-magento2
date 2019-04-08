<?php

namespace Moneris\KountIntegration\DataProvider;

class KountDataProvider
{
    private $data = [];

    public function setAdditionalData($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function getAdditionalData($key)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        } else {
            return null;
        }
    }
}