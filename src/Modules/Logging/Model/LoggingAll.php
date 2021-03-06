<?php

Namespace Model;

class LoggingAll extends BaseLinuxApp {

    // Compatibility
    public $os = array("any") ;
    public $linuxType = array("any") ;
    public $distros = array("any") ;
    public $versions = array("any") ;
    public $architectures = array("any") ;

    // Model Group
    public $modelGroup = array("Default") ;

    // Model Group
    private $logMessage = null ;

    public function __construct($params) {
        parent::__construct($params);
        $this->autopilotDefiner = "Logging";
        $this->installCommands = array(
            array("method"=> array("object" => $this, "method" => "setLogMessage", "params" => array()) ),
            array("method"=> array("object" => $this, "method" => "log", "params" => array()) ),);
        $this->uninstallCommands = array();
        $this->programDataFolder = "/opt/Logging"; // command and app dir name
        $this->programNameMachine = "logging"; // command and app dir name
        $this->programNameFriendly = "  Logging!  "; // 12 chars
        $this->programNameInstaller = "Logging";
        $this->initialize();
    }

    public function setLogMessage() {
        if (isset($this->params["log-message"])) {
            $this->logMessage = $this->params["log-message"] ; }
        else {
            $this->logMessage = self::askForInput("Enter Log Message", true) ; }
    }

    public function log($message = null, $source = null, $options=array() ) {
        if (isset($this->logMessage)) { $message = $this->logMessage ; }
        $stx = (strlen($source)>0) ? "[$source] " : "" ;
        $fullMessage = "[Pharaoh Logging] " . $stx . $message ;
        if (!isset($options["display-log"]) && !isset($this->params["display-log"])){
        //    file_put_contents("php://stderr", $fullMessage );
        }
        else if (isset($options["display-log"]) && $options["display-log"] == false ||
            isset($this->params["display-log"]) && $this->params["display-log"] == false ) {
            file_put_contents("php://stderr", $fullMessage."\n" ); }
        if ((isset($options["php-log"]) && $options["php-log"] == true) || (isset($this->params["php-log"]) && $this->params["php-log"] == true) ) {
            error_log($fullMessage) ; }
        if ((isset($options["echo-log"]) && $options["echo-log"] == true) || (isset($this->params["echo-log"]) && $this->params["echo-log"] == true) ) {
            if ($this->isWebSapi()) {
                $registry_values = new \Model\RegistryStore();
                $logs = $registry_values::getValue("logs") ;
                $logs[] = $fullMessage ;
                $registry_values::setValue("logs", $logs) ;

            } else {
                echo $fullMessage."\n" ;

            }}


    }


    private function isWebSapi() {
        if (!in_array(PHP_SAPI, array("cli")))  { return true ; }
        return false ;
    }

}