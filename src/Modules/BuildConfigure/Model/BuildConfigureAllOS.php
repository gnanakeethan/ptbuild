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

    public function getData() {
        if (isset($this->params["item"])) {
            $ret["pipeline"] = $this->getPipeline(); }
        $ret["builders"] = $this->getBuilders();
        $ret["settings"] = $this->getBuilderSettings();
        $ret["fields"] = $this->getBuilderFormFields();
        $ret["stepFields"] = $this->getStepBuildersFormFields();
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

    public function getBuilders() {
        $builderFactory = new \Model\Builder() ;
        $builder = $builderFactory->getModel($this->params);
        return $builder->getBuilders();
    }

    public function getBuilderSettings() {
        $builderFactory = new \Model\Builder() ;
        $builder = $builderFactory->getModel($this->params);
        return $builder->getBuilderSettings();
    }

    public function getBuilderFormFields() {
        $builderFactory = new \Model\Builder() ;
        $builder = $builderFactory->getModel($this->params, "BuilderRepository");
        return $builder->getAllBuildersFormFields();
    }

    public function getStepBuildersFormFields() {
        $builderFactory = new \Model\Builder() ;
        $builder = $builderFactory->getModel($this->params, "BuilderRepository");
        return $builder->getStepBuildersFormFields();
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
            "parameter-description" => $this->params["parameter-description"]) ;

        $ev = $this->runBCEvent("beforePipelineSave") ;
        if ($ev == false) { return false ; }

        if ($this->params["creation"] == "yes") {
            $pipelineDefault = $pipelineFactory->getModel($this->params);
            $pipelineDefault->createPipeline($this->params["project-slug"]) ; }
        $pipelineSaver = $pipelineFactory->getModel($this->params, "PipelineSaver");
        // @todo  dunno y i have to force thi sparam
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
