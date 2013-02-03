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

    protected function _getPendingList() {
        $helper = Mage::helper('pugmore_mageploy');
        $csv = new Varien_File_Csv();
        try {
            $todoList = $csv->getData($helper->getStoragePath().$helper->getAllActionsFilename());
            $doneList = $csv->getData($helper->getStoragePath().$helper->getExecutedActionsFilename());
            $pendingList = array_diff($todoList, $doneList);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'mageploy.log', true);
            $pendingList = array();
        }
        return $pendingList;
    }
    
    public function run() {
        if ($this->getArg('status')) {
            $pendingList = $this->_getPendingList();
            if (count($pendingList)) {
                foreach ($pendingList as $row) {
                    $actionName = sprintf("%s/%s", $row[1], $row[2]);
                    $actionMethod = $row[3];
                    $spacer = str_repeat(" ", 40 - strlen($actionName));
                    printf("%s:%s%s\r\n", $actionName, $spacer, $actionMethod);
                }
                printf("---\r\nTotal pending actions: %d\r\n", count($pendingList));
            } else {
                printf("There aren't any pending actions to execute.\r\n");
            }
        } else if ($this->getArg('run')) {
            $pendingList = $this->_getPendingList();
            if (count($pendingList)) {
                foreach ($pendingList as $row) {
                    $actionRecorderClass = $row[0];
                    $controllerModule = $row[1];
                    $controllerName = $row[2];
                    $controllerClassName = $this->_getControllerClassName($controllerModule, $controllerName);
                    $executed = 0;
                    if (class_exists($actionRecorderClass)) {
                        // @todo include controller file class
                        $controllerFileName = $this->_getControllerClassPath($controllerModule, $controllerName);
                        if (file_exists($controllerFileName)) {
                            include_once $controllerFileName;
                        } else {
                            printf("Error: file '%s' not found!\r\n", $controllerFileName);
                        }
                        if (class_exists($controllerClassName)) {
                            $actionRecorder = new $actionRecorderClass();
                            $parameters = $row[4];
                            $request = $actionRecorder->decode($parameters);
                            $controller = new $controllerClassName($request, new Mage_Core_Controller_Response_Http());
                            $action = $row[3].'Action';
                            $controller->preDispatch();
                            $controller->$action();
                            $executed ++;
                            // @todo register executed action
                        } else {
                            printf("Error: class '%s' not found!\r\n", $controllerClassName);
                        }
                    } else {
                        printf("Error: class '%s' not found!\r\n", $actionRecorderClass);
                    }
                }
                printf("---\r\nExecuted actions: %d/%d\r\n", $executed, count($pendingList));
            } else {
                printf("There aren't any pending actions to execute.\r\n");
            }
        } else {
            echo $this->usageHelp();
        }
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
