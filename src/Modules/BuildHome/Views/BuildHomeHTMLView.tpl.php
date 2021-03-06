<div class="container" id="wrapper">
       
        <div class="navbar-default col-sm-2 sidebar" role="navigation">
		<div class="sidebar-nav navbar-collapse">
			<ul class="nav in" id="side-menu">
				<li class="sidebar-search">
					<div class="input-group custom-search-form hvr-bounce-in"">
						<input type="text" class="form-control" placeholder="Search...">
						<span class="input-group-btn">
							<button class="btn btn-default" type="button">
								<i class="fa fa-search"></i>
							</button>
                        </span>
					</div>
                    <!-- /input-group -->
                </li>
                <li>
                    <a href="/index.php?control=Index&amp;action=show" class="hvr-bounce-in">
                        <i class="fa fa-dashboard fa-fw hvr-bounce-in"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="/index.php?control=BuildList&amp;action=show" class="hvr-bounce-in">
                        <i class="fa fa-bars fa-fw hvr-bounce-in"></i> All Pipelines
                    </a>
                </li>
                <li>
                    <a href="index.php?control=BuildConfigure&action=show&item=<?php echo $pageVars["data"]["pipeline"]["project-slug"] ; ?>" class="hvr-bounce-in">
                        <i class="fa  fa-cog fa-fw hvr-bounce-in"></i> Configure
                    </a>
                </li>
                
                <li>
                    <a href="index.php?control=Workspace&action=show&item=<?php echo $pageVars["data"]["pipeline"]["project-slug"] ; ?>" class="hvr-bounce-in">
                        <i class="fa fa-folder-open-o hvr-bounce-in"></i> Workspace
                    </a>
                </li>
                <li>
                    <a href="index.php?control=BuildMonitor&action=show&item=<?php echo $pageVars["data"]["pipeline"]["project-slug"] ; ?>" class="hvr-bounce-in">
                        <i class="fa fa-bar-chart-o hvr-bounce-in"></i> Monitors
                    </a>
                </li>
                <li>
                    <a href="index.php?control=PipeRunner&action=history&item=<?php echo $pageVars["data"]["pipeline"]["project-slug"] ; ?>"class="hvr-bounce-in">
                        <i class="fa fa-history fa-fw hvr-bounce-in""></i> History <span class="badge"></span>
                    </a>
                </li>
                <li>
                    <a href="index.php?control=BuildHome&action=delete&item=<?php echo $pageVars["data"]["pipeline"]["project-slug"] ; ?>" class="hvr-bounce-in">
                        <i class="fa fa-trash fa-fw hvr-bounce-in""></i> Delete
                    </a>
                </li>
                <li>
                    <a href="index.php?control=PipeRunner&action=start&item=<?php echo $pageVars["data"]["pipeline"]["project-slug"] ; ?>" class="hvr-bounce-in">
                        <i class="fa fa-sign-in fa-fw hvr-bounce-in""></i> Run Now
                    </a>
                </li>
            </ul>
        </div>
        </div>
    
        <div class="col-lg-9">

            <div class="well well-lg ">
           
            <div class="row clearfix no-margin">
            	 <h3 class="text-uppercase text-light ">Pipeline</h3>
                <!--
                <h3><a class="lg-anchor text-light" href="">PTBuild - Pharaoh Tools <i style="font-size: 18px;" class="fa fa-chevron-right"></i></a></h3>
                -->
                <p> Project Name: <?php echo $pageVars["data"]["pipeline"]["project-name"] ; ?></p>
                <p> Project Slug: <?php echo $pageVars["data"]["pipeline"]["project-slug"] ; ?></p>
                <p> Project Desc: <?php echo $pageVars["data"]["pipeline"]["project-description"] ; ?></p>
            </div>
            <hr>
            <div class="row clearfix no-margin build-home-properties">
<!--                <h3><a class="lg-anchor text-light" href="/index.php?control=BuildConfigure&action=show&item=--><?php //echo $pageVars["data"]["pipeline"]["project-slug"] ; ?><!--">-->
<!--                    Configure Pipeline: --><?php //echo $pageVars["data"]["pipeline"]["project-name"] ; ?><!--- <i style="font-size: 18px;" class="fa fa-chevron-right"></i></a></h3>-->

                <div class="pipe-now-status-block pipe-block">
                    <h4 class="propertyTitle">Build Status Currently:</h4>
                    <?php
                    if ($pageVars["data"]["pipeline"]["last_status"]===true) { $sclass = "good" ;}
                    else if ($pageVars["data"]["pipeline"]["last_status"]===false) { $sclass =  "bad" ; }
                    else { $sclass = "unknown" ; } ?>
                    </h3>

                    <div class="current_status current_status_<?php echo $sclass ; ?>">
                        <h3>
                            Status:
                        <?php
                            echo ucfirst($sclass) ; ?>
                        </h3>
                    </div>

                </div>

                <div class="alert alert-info">
                    <h4>Running Builds </h4>
                    <div id="runningBuilds">
                        <p>
                            No builds of this pipeline currently being executed
                        </p>
                    </div>
                </div>

                <!--
                <p>Build Monitors:</p>
                <div class="pipe-monitor-block pipe-block"></div>
                -->
                <div class="pipe-features-block pipe-block">
                    <h4 class="propertyTitle">Build Features:</h4>
                    <div class="col-sm-12">
                    <?php
                    if (isset($pageVars["data"]["features"]) &&
                        count($pageVars["data"]["features"])>0 ) {
                        foreach ($pageVars["data"]["features"] as $build_feature) {
//                            var_dump($build_feature);
                            if (isset($build_feature["hidden"]) && $build_feature["hidden"] != true
                                || !isset($build_feature["hidden"]) ) {
                                echo '<div class="build-feature">' ;
                                echo '<a target="_blank" href="'.$build_feature["model"]["link"].'">' ;
                                echo  '<h3>'.$build_feature["model"]["title"].'</h3>' ;
                                echo  '<img src="'.$build_feature["model"]["image"].'" />' ;
                                echo "</a>" ;
                                echo '</div>' ; } } }
                    ?>
                    </div>
                </div>
                <div class="pipe-history-block pipe-block">
                    <h4 class="propertyTitle">Build History:</h4>
                    <?php
                        if (isset($pageVars["data"]["pipeline"]["build_history"]) &&
                            count($pageVars["data"]["pipeline"]["build_history"])>0 ) {
                            foreach ($pageVars["data"]["pipeline"]["build_history"] as $build_history) {
                                if ($moduleInfo["hidden"] != true) {
                                    echo '<p><a href="/index.php?control=BuildConfigure&action=show&item=">'.$build_history["count"].
                                        ' - '.$build_history["status"].' - '.$build_history["message"]."</p>"; } } }
                    ?>
                </div>

               <hr>
                <p class="text-center">
                Visit <a href="http://www.pharaohtools.com">www.pharaohtools.com</a> for more
            </p>
            </div>

        </div>

    </div>
</div>
<link rel="stylesheet" type="text/css" href="/Assets/Modules/BuildHome/css/buildhome.css">
<script type="text/javascript" src="/Assets/Modules/BuildHome/js/buildhome.js"></script>