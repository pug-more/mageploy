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
    
    private function __getVersion()
    {
        return Mage::getConfig()->getNode('modules/PugMoRe_Mageploy/version');
    }

    protected function _construct()
    {
        $this->_io = new PugMoRe_Mageploy_Model_Io_File();
        return parent::_construct();
    }

    protected function _getControllerClassPath($controllerModule, $controllerName) {
        $parts = explode('_', uc_words($controllerName));
        $file = Mage::getModuleDir('controllers', $controllerModule);
        if (count($parts)) {
            $file .= DS . implode(DS, $parts);
        }
        $file .= 'Controller.php';
        return $file;
    }

    public function _getControllerClassName($controllerModule, $controllerName)
    {
        $class = $controllerModule.'_'.uc_words($controllerName).'Controller';
        return $class;
    }

    public function run() {
        printf("\r\nMageploy v %s\r\n\r\n", $this->__getVersion());
        if ($this->getArg('status')) {
            $pendingList = $this->_io->getPendingList();
            if (count($pendingList)) {
                printf("Pending Actions list:\r\n");
                foreach ($pendingList as $row) {
                    $actionDescr = '* '.$row[PugMoRe_Mageploy_Model_Action_Abstract::INDEX_ACTION_DESCR];
                    $spacer = str_repeat(" ", 40 - strlen($actionDescr));
                    printf("%s\r\n", $actionDescr);
                }
                printf("\r\nTotal pending actions: %d\r\n", count($pendingList));
            } else {
                printf("There aren't any pending actions to execute.\r\n");
            }
        } else if ($this->getArg('run')) {
            $pendingList = $this->_io->getPendingList();
            if (count($pendingList)) {
                $executed = 0;
                foreach ($pendingList as $row) {
                    $actionExecutorClass = $row[PugMoRe_Mageploy_Model_Action_Abstract::INDEX_EXECUTOR_CLASS];
                    $controllerModule = $row[PugMoRe_Mageploy_Model_Action_Abstract::INDEX_CONTROLLER_MODULE];
                    $controllerName = $row[PugMoRe_Mageploy_Model_Action_Abstract::INDEX_CONTROLLER_NAME];
                    $controllerClassName = $this->_getControllerClassName($controllerModule, $controllerName);
                    if (class_exists($actionExecutorClass)) {
                        $controllerFileName = $this->_getControllerClassPath($controllerModule, $controllerName);
                        if (file_exists($controllerFileName)) {
                            include_once $controllerFileName;
                        } else {
                            printf("Error: file '%s' not found!\r\n", $controllerFileName);
                        }
                        if (class_exists($controllerClassName)) {
                            $actionExecutor = new $actionExecutorClass();
                            $parameters = $row[PugMoRe_Mageploy_Model_Action_Abstract::INDEX_ACTION_PARAMS];
                            $request = $actionExecutor->decode($parameters);
                            $controller = new $controllerClassName($request, new Mage_Core_Controller_Response_Http());
                            $action = $row[PugMoRe_Mageploy_Model_Action_Abstract::INDEX_ACTION_NAME].'Action';
                            $controller->preDispatch();
                            $controller->$action();
                            $controller->postDispatch();
                            $executed ++;
                            // register executed action
                            $this->_io->done($row);
                        } else {
                            printf("Error: class '%s' not found!\r\n", $controllerClassName);
                        }
                    } else {
                        printf("Error: class '%s' not found!\r\n", $actionExecutorClass);
                    }
                }
                printf("\r\nExecuted actions: %d/%d\r\n", $executed, count($pendingList));
            } else {
                printf("There aren't any pending actions to execute.\r\n");
            }
        } else {
            echo $this->usageHelp();
        }
        printf("\r\n");
        
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
