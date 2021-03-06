<?php

Namespace Model;

class BuildConfigureAllOS extends Base {

    // Compatibility
    public $os = array("any") ;
    public $linuxType = array("any") ;
    public $distros = array("any") ;
    public $versions = array("any") ;
    public $architectures = array("any") ;

    // Model Group
    public $modelGroup = array("Default") ;

    private $builder ;
    private $builderRepository ;

    public function __construct($params) {
        parent::__construct($params) ;
    }

    public function getData() {

//        file_put_contents("/tmp/mylog.txt", "before pipeline is added: ".microtime()."\n", FILE_APPEND);

        if (isset($this->params["item"])) { $ret["pipeline"] = $this->getPipeline(); }

//        file_put_contents("/tmp/mylog.txt", "after pipeline is added: ".microtime()."\n", FILE_APPEND);
//        file_put_contents("/tmp/mylog.txt", "before builders are added: ".microtime()."\n", FILE_APPEND);

        $ret["builders"] = $this->getBuilders();
//
//        file_put_contents("/tmp/mylog.txt", "after builders are added: ".microtime()."\n", FILE_APPEND);
//        file_put_contents("/tmp/mylog.txt", "before builder settings are added: ".microtime()."\n", FILE_APPEND);

        $ret["settings"] = $this->getBuilderSettings();
//
//        file_put_contents("/tmp/mylog.txt", "after builder settings are added: ".microtime()."\n", FILE_APPEND);
//        file_put_contents("/tmp/mylog.txt", "before builder form fields are added: ".microtime()."\n", FILE_APPEND);

        $ret["fields"] = $this->getBuilderFormFields();
//
//        file_put_contents("/tmp/mylog.txt", "after builder form fields are added: ".microtime()."\n", FILE_APPEND);
//        file_put_contents("/tmp/mylog.txt", "before step builder form fields are added: ".microtime()."\n", FILE_APPEND);

        $ret["stepFields"] = $this->getStepBuildersFormFields();

//        file_put_contents("/tmp/mylog.txt", "after step builder form fields are added: ".microtime()."\n", FILE_APPEND);

        return $ret ;
    }

    public function getCopyData() {
        if (isset($this->params["item"])) { $ret["pipeline"] = $this->getPipeline(); }
        $pipelineFactory = new \Model\Pipeline() ;
        $pipeline = $pipelineFactory->getModel($this->params, "PipelineRepository");
        $ret["pipe_names"] = $pipeline->getPipelineNames() ;
        return $ret ;
    }

    public function saveState() {
        return $this->savePipeline();
    }

    public function getPipeline() {
        $pipelineFactory = new \Model\Pipeline() ;
        $pipeline = $pipelineFactory->getModel($this->params);
        return $pipeline->getPipeline($this->params["item"]);
    }

//    public function getEventNames() {
//        return array_keys($this->getEvents());   }
//
//    public function getEvents() {
//        $ff = array(
//            "beforePipelineSave" => array(""),
//            "beforeCopiedPipelineSave" => array(""),
//            "afterPipelineSave" => array(""),
//            "afterCopiedPipelineSave" => array(""),
//        );
//        return $ff ; }


    private function getBuilder() {
        if (isset($this->builder) && is_object($this->builder)) {
            return $this->builder ;  }
        $builder = RegistryStore::getValue("builderObject") ;
        if (isset($builder) && is_object($builder)) {
            $this->builder = $builder ;
            return $this->builder ;  }
        $builderFactory = new \Model\Builder() ;
        $this->builder = $builderFactory->getModel($this->params);
        RegistryStore::setValue("builderObject", $this->builder) ;
        return $this->builder ;
    }

    private function getBuilderRepository() {
        if (isset($this->builderRepository) && is_object($this->builderRepository)) {
            return $this->builderRepository ;  }
        $builderRepository = RegistryStore::getValue("builderRepositoryObject") ;
        if (isset($builderRepository) && is_object($builderRepository)) {
            $this->builderRepository = $builderRepository ;
            return $this->builderRepository ;  }
        $builderRepositoryFactory = new \Model\Builder() ;
        $this->builderRepository = $builderRepositoryFactory->getModel($this->params, "BuilderRepository");
        RegistryStore::setValue("builderRepositoryObject", $this->builderRepository) ;
        return $this->builderRepository ;
    }

    public function getBuilders() {
        $this->getBuilder() ;
        return $this->builder->getBuilders();
    }

    public function getBuilderSettings() {
        $this->getBuilder() ;
        return $this->builder->getBuilderSettings();
    }

    public function getBuilderFormFields() {
        $this->getBuilderRepository() ;
        return $this->builderRepository->getAllBuildersFormFields();
    }

    public function getStepBuildersFormFields() {
        $this->getBuilderRepository() ;
        return $this->builderRepository->getStepBuildersFormFields();
    }

    public function savePipeline() {
        $this->params["project-slug"] = $this->getFormattedSlug() ;
        $this->params["item"] = $this->params["project-slug"] ;
        $pipelineFactory = new \Model\Pipeline() ;
        // @todo we need to put all of this into modules, as build settings.
        $data = array(
            "project-name" => $this->params["project-name"],
            "project-slug" => $this->params["project-slug"],
            "project-description" => $this->params["project-description"],
            "default-scm-url" => $this->params["default-scm-url"],
            "email-id" => $this->params["email-id"],
            "parameter-status" => $this->params["parameter-status"],
            "parameter-name" => $this->params["parameter-name"],
            "parameter-dvalue" => $this->params["parameter-dvalue"],
            "parameter-input" => "",
            "parameter-description" => $this->params["parameter-description"]
        ) ;

        $ev = $this->runBCEvent("beforePipelineSave") ;
        if ($ev == false) { return false ; }

        if ($this->params["creation"] == "yes") {
            $pipelineDefault = $pipelineFactory->getModel($this->params);
            $pipelineDefault->createPipeline($this->params["project-slug"]) ; }
        $pipelineSaver = $pipelineFactory->getModel($this->params, "PipelineSaver");
        // @todo dunno why i have to force this param
        $pipelineSaver->params["item"] = $this->params["item"];
        $pipelineSaver->savePipeline(array("type" => "Defaults", "data" => $data ));
        $pipelineSaver->savePipeline(array("type" => "Steps", "data" => $this->params["steps"] ));
        $pipelineSaver->savePipeline(array("type" => "Settings", "data" => $this->params["settings"] ));

        $ev = $this->runBCEvent("afterPipelineSave") ;
        if ($ev == false) { return false ; }

        return true ;
    }

    protected function guessPipeName($orig) {
        $pipelineFactory = new \Model\Pipeline() ;
        $pipeline = $pipelineFactory->getModel($this->params, "PipelineRepository");
        $pipe_names = $pipeline->getPipelineNames() ;
        $req = (isset($this->params["project-name"])) ? $this->params["project-name"] : $orig ;
        if (!in_array($req, $pipe_names)) { return $req ; }
        $guess = $req." PIPE" ;
        for ($i=1 ; $i<5001; $i++) {
            $guess = "Copied Pipeline $orig $i" ;
            if (!in_array($guess, $pipe_names)) {
                break ; } }
        return $guess ;
    }

    public function saveCopiedPipeline() {
        if (!isset($this->params["source_pipeline"])) {
            // we dont need to save anything if we have no source
            return false ; }

        $pipelineFactory = new \Model\Pipeline() ;
        $pipelineDefault = $pipelineFactory->getModel($this->params);
        $sourcePipe = $pipelineDefault->getPipeline($this->params["source_pipeline"]) ;

        $pname = $this->guessPipeName($sourcePipe["project-slug"]);
        $this->params["item"] = $this->getFormattedSlug($pname);

        $tempParams = $this->params ;
        $tempParams["item"]  = $this->params["source_pipeline"] ;
        $pipelineDefault = $pipelineFactory->getModel($tempParams);
        $sourcePipe = $pipelineDefault->getPipeline($this->params["source_pipeline"]) ;

        $useParam = isset($this->params["project-description"]) && strlen($this->params["project-description"])>0 ;
        $pdesc = ($useParam) ?
            $this->params["project-description"] :
            $sourcePipe["project-description"] ;

        // @todo we need to put all of this into modules, as build settings.
        $data = array(
            "project-name" => $pname,
            "project-slug" => $this->params["item"],
            "project-description" => $pdesc,
//            "default-scm-url" => $this->params["default-scm-url"],
//            "email-id" => $this->params["email-id"],
//            "parameter-status" => $this->params["parameter-status"],
//            "parameter-name" => $this->params["parameter-name"],
//            "parameter-dvalue" => $this->params["parameter-dvalue"],
//            "parameter-input" => "",
//            "parameter-description" => $this->params["parameter-description"]
        ) ;

        $ev = $this->runBCEvent("beforePipelineSave") ;
        if ($ev == false) { return false ; }
        $ev = $this->runBCEvent("beforeCopiedPipelineSave") ;
        if ($ev == false) { return false ; }

        $pipelineDefault->createPipeline($this->params["item"]) ;
        $pipelineSaver = $pipelineFactory->getModel($this->params, "PipelineSaver");
        // @todo dunno y i have to force this param
        $pipelineSaver->params["item"] = $this->params["item"];
        $pipelineSaver->savePipeline(array("type" => "Defaults", "data" => $data ));
        $pipelineSaver->savePipeline(array("type" => "Steps", "data" => $sourcePipe["steps"] ));
        $pipelineSaver->savePipeline(array("type" => "Settings", "data" => $sourcePipe["settings"] ));

        $ev = $this->runBCEvent("afterPipelineSave") ;
        if ($ev == false) { return false ; }
        $ev = $this->runBCEvent("afterCopiedPipelineSave") ;
        if ($ev == false) { return false ; }

        return $this->params["item"] ;
    }

    private function runBCEvent($name) {
        $this->params["echo-log"] = true ;
        $eventRunnerFactory = new \Model\EventRunner() ;
        $eventRunner = $eventRunnerFactory->getModel($this->params) ;
        $ev = $eventRunner->eventRunner($name) ;
        if ($ev == false) { return false ; }
        return true ;
    }

    private function getFormattedSlug($name = null) {
        $tpn = (!is_null($name)) ? $name : $this->params["project-name"] ;
        if ($this->params["project-slug"] == "") {
            $this->params["project-slug"] = str_replace(" ", "_", $tpn);
            $this->params["project-slug"] = str_replace("'", "", $this->params["project-slug"]);
            $this->params["project-slug"] = str_replace('"', "", $this->params["project-slug"]);
            $this->params["project-slug"] = str_replace("/", "", $this->params["project-slug"]);
            $this->params["project-slug"] = strtolower($this->params["project-slug"]); }
        return $this->params["project-slug"] ;
    }
    
    public function getInstalledPlugins() {
        $plugin = scandir(PLUGININS) ;
        for ($i=0; $i<count($plugin); $i++) {
            if (!in_array($plugin[$i], array(".", "..", "tmpfile"))){
                if(is_dir(PLUGININS.DS.$plugin[$i])) {
                    // @todo if this isnt definitely a build dir ignore maybe
                    $detail['details'][$plugin[$i]] = $this->getInstalledPlugin($plugin[$i]);
                    $detail['data'][$plugin[$i]] = $this->getInstalledPluginData($plugin[$i]); } } }
        return (isset($detail) && is_array($detail)) ? $detail : array() ;
    }

    public function getInstalledPlugin($plugin) {
	$defaultsFile = PLUGININS.DS.$plugin.DS.'details' ;
        if (file_exists($defaultsFile)) {
            $defaultsFileData =  file_get_contents($defaultsFile) ;
            $defaults = json_decode($defaultsFileData, true) ; }
        return  (isset($defaults) && is_array($defaults)) ? $defaults : array() ;
    }

    public function getInstalledPluginData($plugin) {
        $file = PIPEDIR . DS . $this->params["item"] . DS . 'pluginData';
        if ($pluginData = file_get_contents($file)) {
            $pluginData = json_decode($pluginData, true); }
        $defaultsFile = PLUGININS.DS.$plugin.DS.'data' ;
        if (file_exists($defaultsFile)) {
            $defaultsFileData =  file_get_contents($defaultsFile) ;
            $defaults = json_decode($defaultsFileData, true) ;  }
        foreach ($defaults['buildconf'] as $key=>$val) {
            if (isset ($pluginData[$plugin][$val['name']]) ) {
                $value = $pluginData[$plugin][$val['name']];
                $defaults['buildconf'][$key]['value'] = $value; } }
        return  (isset($defaults) && is_array($defaults)) ? $defaults : array() ;
    }

}
