<?php

class PugMoRe_Mageploy_Controller_Response_Http extends Mage_Core_Controller_Response_Http
{
    public function canSendHeaders($throw = false)
    {
        return false;
    }
}
