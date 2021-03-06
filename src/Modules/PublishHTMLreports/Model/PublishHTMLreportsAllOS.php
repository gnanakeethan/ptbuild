<?php

Namespace Model;

class PublishHTMLreportsAllOS extends Base {

    // Compatibility
    public $os = array("any") ;
    public $linuxType = array("any") ;
    public $distros = array("any") ;
    public $versions = array("any") ;
    public $architectures = array("any") ;

    // Model Group
    public $modelGroup = array("Default") ;

    public function getSettingTypes() {
        return array_keys($this->getSettingFormFields());
    }

    public function getSettingFormFields() {
        $ff = array(
            "enabled" =>
            	array(
                	"type" => "boolean",
                	"optional" => true,
                	"name" => "Publish HTML reports on Build Completion?"
            ),
            "fieldsets" => array(
                "reports" => array(
                    "Report_Directory" =>
                    array(
                        "type" => "text",
                        "name" => "HTML Directory to archive",
                        "slug" => "htmlreportdirectory"),
                    "Index_Page" =>
                    array("type" => "text",
                        "name" => "Index Page",
                        "slug" => "indexpage"),
                    "Report_Title" =>
                    array("type" => "text",
                        "name" => "Report Title",
                        "slug" => "reporttitle"))))
		    ;
          return $ff ;}
   
    public function getEventNames() {
        return array_keys($this->getEvents());   }

	public function getEvents() {
		$ff = array("afterBuildComplete" => array("PublishHTMLreports"));
		return $ff ; }

	public function getReportData() {
		$pipeFactory = new \Model\Pipeline();
		$pipeline = $pipeFactory->getModel($this->params);
		$thisPipe = $pipeline->getPipeline($this->params["item"]);
		$settings = $thisPipe["settings"];
		$mn = $this->getModuleName() ;
		$dir = $settings[$mn]["Report_Directory"];
		$indexFile = $settings[$mn]["Index_Page"];
		if (file_exists($dir.$indexFile)) { $root = "" ; }
		else { $root = PIPEDIR.DS.$this->params["item"].DS.'workspace'.DS ; }
		$ff = array("report" => file_get_contents($root.$dir.$indexFile));
		return $ff ; }

	public function PublishHTMLreports() {
        $loggingFactory = new \Model\Logging();
        $this->params["echo-log"] = true ;
        $logging = $loggingFactory->getModel($this->params);
        
	$run = $this->params["run-id"];
        $file = PIPEDIR.DS.$this->params["item"].DS.'settings';
        $steps = file_get_contents($file) ;
        $steps = json_decode($steps, true);
	
	$mn = $this->getModuleName() ;
	if (isset($steps[$mn]["enabled"]) && $steps[$mn]["enabled"] == "on") {

        foreach ($steps[$mn]["reports"] as $reportHash => $reportDetail) {

	$dir = $reportDetail["Report_Directory"];
	if (substr($dir, -1) != DS) { $dir = $dir . DS ;}
		
	$indexFile = $reportDetail["Index_Page"];
	$ReportTitle = $reportDetail["Report_Title"];
	$tmpfile = PIPEDIR.DS.$this->params["item"].DS.'tmpfile';
	$raw = file_get_contents($tmpfile); 	
	if (!$raw) {
        $logging->log("Report not generated", $this->getModuleName());	}
    else {
        $slug = "Report of Pipeline ".$this->params["item"]." for run-id ".$this->params["run-id"];
        $byline = "Ptbuild - Pharaoh Tools, Configuration, Infrastructure and Systems Automation Management in PHP. ";
        $html = nl2br(htmlspecialchars($raw));
        $html = str_replace("&lt;br /&gt;","<br />",$html);
        $html = preg_replace('/\s\s+/', ' ', $html);
        $html = preg_replace('/\s(\w+:\/\/)(\S+)/', ' <a href="\\1\\2" target="_blank">\\1\\2</a>', $html);

$output =<<< HEADER
<html>
<head><title>"$ReportTitle"</title>
<style>
.slug {font-size: 15pt; font-weight: bold; font-style: italic}
.byline { font-style: italic }
</style>
</head>
<body>
HEADER;
$output .= "<div class='slug'>$slug</div>";
$output .= "<div class='byline'>By $byline</div><p />";
$output .= "<div>$html</div>";
$output .=<<< FOOTER
</body>
</html>
FOOTER;
	//save reference	
	$reportRef = PIPEDIR.DS.$this->params["item"].DS.'workspace'.DS.'HTMLreports'.DS; 
	if (!file_exists($reportRef)) { mkdir($reportRef, 0777); }
	file_put_contents($reportRef.$indexFile . '-' . date("l jS \of F Y h:i:s A"), $output);
	$source=$dir.$indexFile;
	if(file_put_contents($source,$output)) {	return true;	}
	else { return false; } } } }
   else {
//$logging->log ("Publish HTML reports ignoring...", $this->getModuleName() ) ;
//            	return true ;
   }
}

}