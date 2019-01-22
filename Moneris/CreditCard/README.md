The Collins Harper Measure Unit module extends base magento for more accurate Shipping Rates.
Please read about our Boxing module on collinsharper.com.


Extract this package into the root of your magento installation

 chown -R daemon:daemon .
 TODO  put perm fixes in here
 php bin/magento module:disable Moneris_CreditCard --clear-static-content
  php bin/magento setup:upgrade
 rm -rf var/page_cache/* var/generation/* var/di/* var/cache/mage-* 

 
  chown -R daemon:daemon .