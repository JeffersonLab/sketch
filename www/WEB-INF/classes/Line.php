<?php
namespace sketch;

$scriptdir = dirname(__FILE__);
require $scriptdir . '/LineUtility.php';
require $scriptdir . '/Cluster.php';
require $scriptdir . '/Page.php';

class Line {
    public static $PAGE_WIDTH = 720; // 96 DPI X 7.5 inches (assumes 0.5 left and 0.5 right margin) on 8.5 inch paper
    public static $PAGE_HEIGHT = 960; // 96 DPI X 10 inches (assumes 0.5 top and 0.5 bottom margin) on 11 inch paper
    public static $SYMBOL_WIDTH = 30;
    public static $SYMBOL_HEIGHT = 30;
    public static $LINE_WIDTH = 360;
    public static $LINE_HEADER_HEIGHT = 30;
    public static $VERTICAL_PADDING = 180; 
    public static $ELEMENT_FONT_SIZE = 14;
    public static $PROPERTY_FONT_SIZE = 12;
    public static $NOMENCLATURE_VIOLATIONS = array("TAGGERB","HPSHaloCounters","PRADTARGET","COLBHALLB","CENTEROFHALLB");
    public static $CLUSTER_PADDING = 60;
    public static $BRANCH_PADDING = 120;
    private static $REMOTE_SYMBOL_PATH = "resources/svg/symbols.svg";
    private static $BRANCH_CONNECT_PADDING = 15;
    public $isContinuation = false;
    public $isBranch = false;
    public $interval;
    public $branchParentElements = array();
    public $joinParentElements = array();
    public $lane;
    private $name;
    private $displayName;
    private $description;
    private $elements;
    private $clusters;
    private $pages;
    private $width;
    private $height;
    private $isCluster;
    private $isProperties;
    private $startOfClusterElementNames;
    private $cedHostname;
    private $isLinkCed;

    public function __construct($name, $displayName, $description, $elements, $isCluster, $isProperties, $isPaginate, $cedHostname, $isLinkCed) {
        $this->name = $name;
        $this->displayName = $displayName;
        $this->description = $description;
        $this->elements = $elements;
        $this->width = Line::$LINE_WIDTH;
        $this->height = (count($this->elements) * self::$SYMBOL_HEIGHT * 2) + self::$VERTICAL_PADDING + self::$LINE_HEADER_HEIGHT;
        $this->isCluster = $isCluster;
        $this->isProperties = $isProperties;
        $this->isPaginate = $isPaginate;
        $this->cedHostname = $cedHostname;
        $this->isLinkCed = $isLinkCed;

        if($isCluster) {
            $this->organizeElementsIntoClusters();
            $this->height = $this->height + (self::$CLUSTER_PADDING * (count($this->clusters) - 1));
             
            $this->startOfClusterElementNames = array();

            foreach($this->clusters as $cluster) {
                //error_log("Cluster: " . $cluster->name);
                //$lastIndex = count($cluster->elements) - 1;
                //$lastElement = $cluster->elements[$lastIndex];
                $firstElement = $cluster->elements[0];
                array_push($this->startOfClusterElementNames, $firstElement->name);
                //foreach($cluster->elements as $element) {
                //    error_log("Element: " . $element->name);
                //}
                //error_log(" ");
            }
        } else {
            $this->clusters = array();
            $cluster = new Cluster();
            $cluster->elements = $this->elements;
            array_push($this->clusters, $cluster);
        }

        if($isPaginate) {
            //error_log("Organizing elements into pages");
            $this->organizeElementsIntoPages(100, Line::$PAGE_HEIGHT);

            /*$i = 1;
            foreach($this->pages as $page) {
                error_log("Page: " . $i++);

                foreach($page->elements as $element) {
                    error_log("Element: " . $element->name);
                }
            }*/
        } 
    }

    public function recalculateHeightForJoins() {
        if(!$this->isPaginate) {
            //error_log("Recalc: We're not paginated so calculate extra height needed for joins");
            if(count($this->elements) > 0) {
                //error_log("Recalc: found elements...");
                foreach($this->elements as $element) {
                    if(array_key_exists($element->name, $this->joinParentElements)) {
                        $joinLines = $this->joinParentElements[$element->name];
                        //error_log("Recalc: Join lines encountered: " . count($joinLines));
                        foreach($joinLines as $joinLine) {
                            $parentElementOffset = $this->lane->getElementOffset($element->name);
                            $childElementOffset = $joinLine->lane->getElementOffset(""); // unknown element name returns offset to end
                            //error_log("Recalc: parentElementOffset: " . $parentElementOffset . "; childElementOffset: " . $childElementOffset);

                            if($childElementOffset > $parentElementOffset) {
                                $diff = ($childElementOffset - $parentElementOffset) - (Line::$SYMBOL_HEIGHT / 2) - Line::$SYMBOL_HEIGHT * 2;
                                //error_log("line: " . $this->getName());
                                //error_log("old height: " . $this->height);
                                $this->height = $this->height + $diff;
                                //error_log("Recalc: Adding height: " . $diff);
                                //error_log("new height: " . $this->height);
                            }
                        }
                    }
                }
            }
        }
    }

    private function getElementClusterName($element) {
        $name = $element->name;
 
        if($element->type == "CryoCavity") {
            $name = "CCA" . $element->name;
        }

        if(in_array($element->name, self::$NOMENCLATURE_VIOLATIONS)) {
            $clusterName = "++";
        } else if(strlen($name) >= 7) {
            $clusterName = substr($name, 5, 2);
        } else {
            $clusterName = "__";
        }

        return $clusterName;
    }

    private function organizeElementsIntoPages($firstPageHeaderHeight, $pageHeight) {
        $this->pages = array();
   
        if(count($this->elements) > 0) {
            $page = new Page();
            $page->elements = array();

            $STARTING_Y = -self::$SYMBOL_HEIGHT;
            $y = $STARTING_Y + $firstPageHeaderHeight;
            $firstCluster = true;

            foreach($this->elements as $element) {
                $trailingElements = true;
                $dy = self::$SYMBOL_HEIGHT * 2;

                if($this->isCluster && !$firstCluster && in_array($element->name, $this->startOfClusterElementNames)) {
                    $dy = $dy + self::$CLUSTER_PADDING;
                    $page->clusterCount++;
                }

                $y = $y + $dy;
 
                $firstCluster = false;

                array_push($page->elements, $element);
                
                if($y > ($pageHeight - Line::$VERTICAL_PADDING)) {                   
                    array_push($this->pages, $page);
                    $page = new Page();
                    $page->elements = array();
                    $y = $STARTING_Y;
                    $trailingElements = false;
                }
            }
 
            if($trailingElements) {
                //error_log("Adding trailing page");
                array_push($this->pages, $page);
            }
        } 
    }

    private function organizeElementsIntoClusters() {
        $this->clusters = array();       
 
        if(count($this->elements) > 0) {

            $element = $this->elements[0];
            
            $clusterName = $this->getElementClusterName($element);
            $cluster = new Cluster();
            $cluster->name = $clusterName;
            $cluster->elements = array();
            array_push($cluster->elements, $element);
            array_push($this->clusters, $cluster);
            $lastClusterName = $clusterName;

            for($i = 1; $i < count($this->elements); $i++) {
                $element = $this->elements[$i];
                //error_log("Handling element: " . $element->name);
                $clusterName = $this->getElementClusterName($element);

                if($clusterName == $lastClusterName) {
                    //error_log("Adding to existing cluster: " . $clusterName);
                    $lastIndex = count($this->clusters) - 1;
                    array_push($this->clusters[$lastIndex]->elements, $element);
                } else {
                    //error_log("Created new cluster: " . $clusterName);
                    $cluster = new Cluster();
                    $cluster->name = $clusterName;
                    $cluster->elements = array();
                    array_push($cluster->elements, $element);
                    array_push($this->clusters, $cluster);
                    $lastClusterName = $clusterName;
                }
            }
        }
    }

    public function getName() {
        return $this->name;
    }

    public function getWidth() {
        return $this->width;
    }

    public function getHeight() {
        return $this->height;
    }

    public function getElements() {
        return $this->elements;
    }

    public function getClusters() {
        return $this->clusters;
    }

    public function getPages() {
        return is_array($this->pages) ? $this->pages : [];
    }

    public function draw($x, $y, $pageIndex = 0, $hasNextPage = false, $ECHO_INDENT = "    ") {
        $firstColumnMaxX = $x + ($this->width / 2) - self::$SYMBOL_WIDTH;
        $middleColumnMinX = $x + ($this->width / 2) - (self::$SYMBOL_WIDTH / 2);
        $lastColumnMinX = $x + ($this->width / 2) + self::$SYMBOL_WIDTH;
        $columnTextYOffset = -(self::$SYMBOL_HEIGHT - 5 + (self::$SYMBOL_HEIGHT / 2));

        $beamlineYOne = $y + self::$LINE_HEADER_HEIGHT; 
        $beamlineYTwo = $y + $this->height - (self::$VERTICAL_PADDING + self::$LINE_HEADER_HEIGHT);

        //error_log("line: " . $this->getName());
        //error_log("drawing with height: " . $this->height);

        if($this->isContinuation) {
            $beamlineYTwo = $beamlineYTwo + self::$VERTICAL_PADDING;
        }

        if($this->isPaginate) {
            if($hasNextPage) {
                $beamlineYTwo = Line::$PAGE_HEIGHT;
            } else {
                $beamlineYTwo = $y + (count($this->pages[$pageIndex]->elements) * Line::$SYMBOL_HEIGHT * 2) + (($this->pages[$pageIndex]->clusterCount - 1) * Line::$CLUSTER_PADDING);
            }

            if($pageIndex > 0) {
                $beamlineYOne = 0;
            }
        }

        //error_log("y = " . $y);
        //error_log("Setting line y1 = " . $beamlineYOne);
        //error_log("Setting line y2 = " . $beamlineYTwo);

        $titleY = $y;
        if($this->isBranch && !$this->isPaginate) {
            $titleY = $titleY - (Line::$BRANCH_PADDING / 2);
            $beamlineYOne = $beamlineYOne - Line::$BRANCH_CONNECT_PADDING;
        }

        if(!$this->isPaginate || ($this->isPaginate && $pageIndex === 0)) {
            echo $ECHO_INDENT . '<text x="' . ($x + ($this->width / 2)) . '" y="' . $titleY . '" text-anchor="middle" font-weight="bold" title="' . $this->name . '">' . $this->displayName . ' Line</text>' . PHP_EOL;
            
            if($this->description !== null) {
                echo $ECHO_INDENT . '<text x="' . ($x + ($this->width / 2)) . '" y="' . ($titleY + 15) . '" font-size="14" text-anchor="middle" font-style="italic">(' . $this->description . ')</text>' . PHP_EOL;
            }
        }

        echo $ECHO_INDENT . '<line class="beam-line" x1="' . ($x + ($this->width / 2)) . '" y1="' . $beamlineYOne . '" x2="' . ($x + ($this->width / 2)) . '" y2="' . $beamlineYTwo . '" stroke="gray" stroke-width="2"/>' . PHP_EOL;
        echo $ECHO_INDENT . '<g class="beam-line-columns" font-size="' . self::$ELEMENT_FONT_SIZE . '" transform="translate(0, ' . ($y + self::$LINE_HEADER_HEIGHT) . ')">' . PHP_EOL;
        $ECHO_INDENT = $ECHO_INDENT . "    ";
        echo $ECHO_INDENT . '<g class="symbol-column">' . PHP_EOL;
        $ECHO_INDENT = $ECHO_INDENT . "    ";


        $lineUtil = LineUtility::getInstance();


        $y = 0;
        //error_log("Drawing Segment: " . $this->name);
        //error_log("Element Count: " . count($this->elements));
        //error_log("Branch Element Count: " . count($this->branchParentElements));

        /*foreach($this->branchParentElements as $key => $value) {
            error_log("Key: " . $key . ", Value count: " . count($value));
        }*/

        $elementsToDisplay = $this->elements;
        $symbolPath = "";
        $joinElementPaddingMap = array();

        if($this->isPaginate) {
            //$symbolPath = Line::$REMOTE_SYMBOL_PATH;
            if(array_key_exists($pageIndex, $this->pages)) {
                //error_log("Drawing page: " . $pageIndex);
                $elementsToDisplay = $this->pages[$pageIndex]->elements;
                //error_log("Element count: " . count($elementsToDisplay));
            } else {
                //error_log("No page for index: " . $pageIndex);
                return;
            }
        } else {
            //error_log("Drawing one large image (not paginating)");
        }

        foreach($elementsToDisplay as $element) {

            if($this->isCluster && $y != 0 && in_array($element->name, $this->startOfClusterElementNames)) {
                $y = $y + self::$CLUSTER_PADDING;
            }   

            if(array_key_exists($element->name, $this->branchParentElements)) {
                $branchLines = $this->branchParentElements[$element->name];
                //error_log("Drawing branch lines: " . count($branchOffsets));
                foreach($branchLines as $branchLine) {
                    $laneOffset = $branchLine->lane->getLaneIndex();
                    //error_log("Lane Offset: " . $laneOffset);
                    echo $ECHO_INDENT . '<line x1="' . ($x + ($this->width / 2)) . '" y1="' . ($y + (Line::$SYMBOL_HEIGHT)) . '" x2="' . (($this->width / 2) + ($laneOffset * $this->width)) . '" y2="' . ($y + Line::$BRANCH_PADDING - Line::$BRANCH_CONNECT_PADDING) . '" stroke="gray" stroke-width="2"/>' . PHP_EOL;
                    if($this->isPaginate) {
                        echo $ECHO_INDENT . '<line x1="' . (($this->width / 2) + ($laneOffset * $this->width)) . '" y1="' . ($y + Line::$BRANCH_PADDING - Line::$BRANCH_CONNECT_PADDING) . '" x2="' . (($this->width / 2) + ($laneOffset * $this->width)) . '" y2="' . Line::$PAGE_HEIGHT . '" stroke="gray" stroke-width="2"/>' . PHP_EOL;;
                    }
                }
            }

            
            if(array_key_exists($element->name, $this->joinParentElements)) {
                $joinLines = $this->joinParentElements[$element->name];
                //error_log("Drawing join lines: " . count($joinLines));
                foreach($joinLines as $joinLine) {
                    $laneOffset = $joinLine->lane->getLaneIndex();
                    //error_log("Lane Offset: " . $laneOffset);
                    
                    if($this->isPaginate) {
                        //error_log("Ommitting joing lines due to pagination :: Feature not supported");
                    } else {
                        //error_log("We're not paginated so let's do join lines");
                        $parentElementOffset = $this->lane->getElementOffset($element->name);
                        $childElementOffset = $joinLine->lane->getElementOffset(""); // unknown element name returns offset to end                  
                        //error_log("parentElementOffset: " . $parentElementOffset . "; childElementOffset: " . $childElementOffset); 
                    
                        if($childElementOffset > $parentElementOffset) {
                            //error_log("Must pad parent join line since child extends past parent"); 
                            $diff = ($childElementOffset - $parentElementOffset) - (Line::$SYMBOL_HEIGHT / 2) - Line::$SYMBOL_HEIGHT * 2;
                            //error_log("diff: " . $diff);
                            $y = $y + $diff;
                            $joinElementPaddingMap[$element->name] = $diff;
                        } else {
                            //error_log("Must pad child join line since parent extends past child");
                            $x1 = (($this->width / 2) + ($laneOffset * $this->width));
                            $x2 = $x1;
                            $y2 = $y - Line::$BRANCH_PADDING + Line::$BRANCH_CONNECT_PADDING;
                            $diff = ($parentElementOffset - $childElementOffset); 
                            $y1 = $y2 - $diff - Line::$BRANCH_PADDING + Line::$BRANCH_CONNECT_PADDING + Line::$SYMBOL_HEIGHT;
                            //error_log("y1: " . $y1 . "; y2: " . $y2 . "; diff: " . $diff);
                            echo $ECHO_INDENT . '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="gray" stroke-width="2"/>' . PHP_EOL;
                        }

                        echo $ECHO_INDENT . '<line x1="' . ($x + ($this->width / 2)) . '" y1="' . $y . '" x2="' . (($this->width / 2) + ($laneOffset * $this->width)) . '" y2="' . ($y - Line::$BRANCH_PADDING + Line::$BRANCH_CONNECT_PADDING) . '" stroke="gray" stroke-width="2"/>' . PHP_EOL;
                    }
                }
            }            


            $type = $lineUtil->getCommonType($element);
            $symbolName = $lineUtil->getSymbolNameForType($type);

            echo $ECHO_INDENT . '<use title="S: ' . number_format($element->s, 2) . '" width="' . self::$SYMBOL_WIDTH . '" height="' . self::$SYMBOL_HEIGHT . '" x="' . $middleColumnMinX . '" y="' . $y . '" xlink:href="' . $symbolPath . $symbolName . '"/>' . PHP_EOL;

                //if(array_key_exists($element->name, $this->branchParentElements)) {
                //    $y = $y + (self::$SYMBOL_HEIGHT * 8);
                //} else {
                    $y = $y + (self::$SYMBOL_HEIGHT * 2);
                //}
        }

        $ECHO_INDENT = substr($ECHO_INDENT, 0, strlen($ECHO_INDENT) - 4);
        echo $ECHO_INDENT . '</g>' . PHP_EOL;

        echo $ECHO_INDENT . '<g class="text-columns" transform="translate(0, ' . $columnTextYOffset . ')">' . PHP_EOL;
        $ECHO_INDENT = $ECHO_INDENT . "    ";
        echo $ECHO_INDENT . '<text class="type-column" text-anchor="end">' . PHP_EOL;
        $ECHO_INDENT = $ECHO_INDENT . "    ";

        $y = 0;

        foreach($elementsToDisplay as $element) {

           $dy = self::$SYMBOL_HEIGHT * 2;

            if($this->isCluster && $y != 0 && in_array($element->name, $this->startOfClusterElementNames)) {
                $dy = $dy + self::$CLUSTER_PADDING;
            }

            if(array_key_exists($element->name, $joinElementPaddingMap)) {
                //error_log("adding padding to type");
                $dy = $dy + $joinElementPaddingMap[$element->name];
            }

            $type = $lineUtil->getCommonType($element);

            echo $ECHO_INDENT . '<tspan title="ModeledAs: ' . (($element->modeledAs === null) ? "NOTMODELED" : $element->modeledAs) .  '" x="' . $firstColumnMaxX . '" dy="' . $dy . '">' . $type .  (($element->modeledAs === null) ? "&#8224;" : "") . '</tspan>' . PHP_EOL;

            $y = $y + $dy;


            /*if(count($element->segments) > 1) {
                error_log("More than one segment encountered for element: " . $element->name);
                foreach($element->segments as $segment) {
                    error_log($segment);
                }
            }

            if(in_array($element->name, $segmentStartElements)) {
                echo $ECHO_INDENT . '<tspan x="' . $firstColumnMaxX . '" dy="' . ($symbolHeight * 6) . '" xml:space="preserve"> </tspan>' . PHP_EOL;
            }*/
        }

        $ECHO_INDENT = substr($ECHO_INDENT, 0, strlen($ECHO_INDENT) - 4);
        echo $ECHO_INDENT . '</text>' . PHP_EOL;
        echo $ECHO_INDENT . '<text class="name-column" text-anchor="start">' . PHP_EOL;

        $ECHO_INDENT = $ECHO_INDENT . "    ";

        $y = 0;

        foreach($elementsToDisplay as $element) {
           $dy = self::$SYMBOL_HEIGHT * 2;

            if($this->isCluster && $y != 0 && in_array($element->name, $this->startOfClusterElementNames)) {
                $dy = $dy + self::$CLUSTER_PADDING;
            }

            if(array_key_exists($element->name, $joinElementPaddingMap)) {
                //error_log("adding padding to name");
                $dy = $dy + $joinElementPaddingMap[$element->name];
            }

            if($this->isLinkCed) {
                $url = $this->cedHostname . '/elem/' . \sketch\encode($element->name);
             } else {
                $url = getenv("SRM_SERVER_URL") . "/reports/component/detail?name=" . \sketch\encode($element->name);
             }

            echo $ECHO_INDENT . '<tspan x="' . $lastColumnMinX . '" dy="' . $dy . '"><a title="S: ' . number_format($element->s, 2) . '" xlink:href="' . $url . '" target="_blank">' . $element->name . ($element->unpowered ? '*' : '') . '</a></tspan>' . PHP_EOL;

            $y = $y + $dy;

            /*if(in_array($element->name, $segmentStartElements)) {
                echo $ECHO_INDENT . '<tspan x="' . $lastColumnMinX . '" dy="' . (self::$SYMBOL_HEIGHT * 6) . '" xml:space="preserve"> </tspan>' . PHP_EOL;
            }*/
        }

        $ECHO_INDENT = substr($ECHO_INDENT, 0, strlen($ECHO_INDENT) - 4);
        echo $ECHO_INDENT . '</text>' . PHP_EOL;

        $ECHO_INDENT = substr($ECHO_INDENT, 0, strlen($ECHO_INDENT) - 4);
        echo $ECHO_INDENT . '</g>' . PHP_EOL;

        if($this->isProperties) {
            echo $ECHO_INDENT . '<g class="properties-column" font-size="' . self::$PROPERTY_FONT_SIZE . '" transform="translate(' . ($lastColumnMinX + 30). ', 0)">' . PHP_EOL;
            $ECHO_INDENT = $ECHO_INDENT . "    ";
       
            $STARTING_Y = -self::$SYMBOL_HEIGHT;
            $y = $STARTING_Y;

            foreach($elementsToDisplay as $element) {
                $dy = self::$SYMBOL_HEIGHT * 2;

                if($this->isCluster && $y != $STARTING_Y && in_array($element->name, $this->startOfClusterElementNames)) {
                    $dy = $dy + self::$CLUSTER_PADDING;
                }

                $y = $y + $dy;

                echo $ECHO_INDENT . '<rect x="0" y="' . $y . '" width="100" height="30" fill="white"/>' . PHP_EOL;
                echo $ECHO_INDENT . '<text x="5" y="' . ($y + 20) . '">S:</text>' . PHP_EOL;
                echo $ECHO_INDENT . '<text x="95" y="' . ($y + 20) . '" text-anchor="end">' . number_format($element->s, 2) . '</text>' . PHP_EOL;
            }

            $ECHO_INDENT = substr($ECHO_INDENT, 0, strlen($ECHO_INDENT) - 4);
            echo $ECHO_INDENT . '</g>' . PHP_EOL;
        }

        $ECHO_INDENT = substr($ECHO_INDENT, 0, strlen($ECHO_INDENT) - 4);
        echo $ECHO_INDENT . '</g>' . PHP_EOL;
    }
}
?>
