<?php
if (file_exists('abstract.php')) {
    require_once 'abstract.php';
} else { 
    require_once 'shell/abstract.php';
}

class Mage_Shell_Mageploy extends Mage_Shell_Abstract {

    protected $_options = array(
        'status'    => 'Show if there are any changes to be imported',
        'run'       => 'Import changes',
    );
    
    public function run() {
        echo $this->usageHelp();
    }
    
    public function usageHelp() {
        $help = "\r\nUsage:\tphp mageploy.php --[options]\r\n\r\n";
        foreach ($this->_options as $option => $description) {
            $help .= "--$option".  str_repeat(" ", 20 - strlen($option))."$description\r\n";
        }
        return $help."\r\n";
    }

}

$shell = new Mage_Shell_Mageploy();
$shell->run();
