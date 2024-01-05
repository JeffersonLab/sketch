<?php
    $contextPath = '/sketch';
    $resourceVersion = '1';
?>
                </div>
            </div>
        </div>
        <script type="text/javascript" src="//ace.jlab.org/cdn/jquery/1.10.2.min.js"></script>
        <script type="text/javascript" src="//ace.jlab.org/cdn/jquery-ui/1.10.3/jquery-ui.min.js"></script>
        <script type="text/javascript" src="//ace.jlab.org/cdn/jlab-theme/smoothness/1.5/js/smoothness.min.js"></script>
	<?php
	    if(isset($script_array)) { 
                foreach($script_array as $script) {
                    echo '    <script type="text/javascript" src="' . $contextPath . '/resources/js/' . $script . '?v=' . $resourceVersion . '"></script>' . "\n";
                } 
            }
        ?>
        <script type="text/javascript">
            jlab.contextPath = '<?php echo $contextPath; ?>';
        </script>
    </body>
</html>

