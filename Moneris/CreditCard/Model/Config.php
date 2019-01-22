<?php
/**
* Copyright © 2017 CyberSource. All rights reserved.
* See accompanying License.txt for applicable terms of use and license.
*/
namespace Moneris\CreditCard\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Config
 * @package CyberSource\Core\Model
 * @codeCoverageIgnore
 */
class Config extends \Magento\Payment\Gateway\Config\Config
{
    
    protected $method;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $methodCode,
        $pathPattern
    ) {
        parent::__construct($scopeConfig, 'chmoneriscc', $pathPattern);
    }
}
