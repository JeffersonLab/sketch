<?php
namespace sketch;

$scriptdir = dirname(__FILE__);
require $scriptdir . '/Element.php';
require $scriptdir . '/Diagram.php';
require $scriptdir . '/Line.php';
require $scriptdir . '/Lane.php';
require $scriptdir . '/Interval.php';

/**
 * Factory to obtain a CED Diagram.
 */
class DiagramFactory {

    private $cedHostname; 
    private $tipToParentMap;
    private $tailToParentMap;
    private $leftLineTipElements;
    private $duplicatesMap;
    private $lastSiblingElement;
    private $isConnect;
    private $isProperties;
    private $isCluster;
    private $isPaginate;
    private $isLinkCed;

    /**
     * Create a new DiagramFactory.
     *
     * @param string $cedHostname hostanme of ced webserver
     * @param boolean $isProperties true to display properties
     * @param boolean $isCluster true to cluster elements by nomenclature 
     * @param boolean $isConnect true to connect lines together (both branches and straight line continuations)
     * @param boolean $isPaginate true to generate paginated diagrams 
     * @param boolean $isLinkCed true to link elements to the CED/LED, otherwise link to HCO
     */
    public function __construct($cedHostname, $isProperties, $isCluster, $isConnect, $isPaginate, $isLinkCed) {
        $this->cedHostname = $cedHostname;
        $this->isProperties = $isProperties;
        $this->isCluster = $isCluster;
        $this->isConnect = $isConnect;
        $this->isPaginate = $isPaginate; 
        $this->isLinkCed = $isLinkCed;

        $this->leftLineTipElements = array(
            "MBH3I01H", // GUN3
            "VBV1D00", // 1D
            "MBH5D00H", // 5D
            "ITV4D00", // 4D
            "VBV1P01", // HALL A Compton
            "IPM3P01" // HALL C Compton
        );
    }

    /**
     * Obtain a Diagram from a zone object hierarchy.  Each zone must have a name string property and an isContinuous boolean property.
     * In addition, each zone must have either an elements array property or a subzone array property.  Each element must have a name property and an array named
     * properties, which contains values for S, SegMask, NameAlias, ModeledAs, and Unpowered.
     *
     * @param object $zone hierarchical zone object
     */
    public function getDiagramFromZoneHierarchy($zone) {
        $lines = $this->loadLinesFromHierarchy($zone);
        $zoneName = $zone->name;
        $zoneDescription = $zone->description;
        return $this->constructFromLines($zoneName, $zoneDescription, $lines);
    }

    /**
     * Obtain a Diagram via JSON data source given a zone name and workspace name.
     *
     * @param string $zoneName the CED zone name
     * @param string $workspaceName the CED workspace name or null to use the default (OPS)
     */
    public function getDiagramFromJson($zoneName, $workspaceName) {
        $jsonObj = $this->loadZoneHierarchyFromJson($zoneName, $workspaceName);

        $zone = $jsonObj->zone; 
        $description = $zone->description;

        $lines = $this->loadLinesFromHierarchy($zone);

        return $this->constructFromLines($zoneName, $description, $lines);
    } 

    /**
     * Obtain a Diagram via command line data source given a zone name and workspace name.
     *
     * WARNING: this method uses segmasks instead of a zone hierarchy and also manually assigned line connection points.
     *
     * @param string $zoneName the CED zone name
     * @param string $workspaceName the CED workspace name or null to use the default (OPS)
     */
    public function getDiagramFromCommandLine($zoneName, $workspaceName) {
        $this->resetBookKeepingVars();
        $elements = $this->loadElementsFromCommandLine($zoneName, $workspaceName);
        $lines = $this->organizeIntoLines($elements);

        return $this->constructFromLines($zoneName, null, $lines);
    }

    private function constructFromLines($zoneName, $zoneDescription, $lines) {
        $lanes = $this->organizeIntoLanes($lines);
        $numberOfPages = 1;
        if($this->isPaginate) {
            //error_log("Paginating Diagram");
            $numberOfPages = $this->calculateMaxNumPages($lanes);
            //error_log("Num Pages: " . $numberOfPages);
        }

        $zoneName = $this->formatTitle($zoneName);
        $zoneDescription = $this->formatTitle($zoneDescription, 48);

        if(count($lines) === 1) {
            $zoneDescription = null;
        }

        return new Diagram($zoneName . " Zone Diagram", $zoneDescription, $lanes, $numberOfPages, $this->isPaginate);
    }

    private function formatTitle($name, $length = 32) {
        if($name === null || strlen($name) === 0) {
            $name = null;
        } else {
            $name = mb_strimwidth($name, 0, $length, "...");
        }

        return $name;
    }

    private function calculateMaxNumPages($lanes) {
        $maxPages = 1;

        if(count($lanes) > 0) {
            $maxPages = $lanes[0]->getPageCount();

            $lanesToCheck = count($lanes);
            if($this->isPaginate && $lanesToCheck > 2) { // TRUNCATE to TWO LANES ANYWAYS
                $lanesToCheck = 2;
            }

            for($i = 1; $i < $lanesToCheck; $i++) {
                if($maxPages < $lanes[$i]->getPageCount()) {
                    $maxPages = $lanes[$i]->getPageCount();
                }
            }
        }

        return $maxPages;
    }

    private function getElementsFromZone($zone) {
        $elements = array();

        foreach($zone->elements as $element) {
            $e = new Element();
            $e->name = $element->name;
            $e->type = $element->type;
            $e->s = $element->properties->S;

            if(property_exists($element->properties, "ModeledAs")) {
                if($element->properties->ModeledAs !== "NotModeled") {
                    $e->modeledAs = $element->properties->ModeledAs;
                }
            }
            
            $e->segments = array();

            $segmentsAndAreas = explode("+", $element->properties->SegMask);

            foreach($segmentsAndAreas as $candidate) {
                if(substr($candidate, 0, 1) === "S") {
                    array_push($e->segments, $candidate);
                }
            }

            if(property_exists($element->properties, "Unpowered")) {
                $e->unpowered = ($element->properties->Unpowered == "1");
            }

            if(!array_key_exists($e->name, $this->duplicatesMap)) {
                array_push($elements, $e);
                $this->duplicatesMap[$e->name] = $e;
            } else {
                //error_log("Found duplicate element (connection point): " . $e->name . "; zone: " . $zone->name);
                if(!$this->isConnect) {
                    array_push($elements, $e);
                }
            }
        }

        return $elements;
    }

    private function addLineFromZoneRecursive($zone, & $lines, $isParentContiguous = false) {
            //error_log("Parsing zone: " . $zone->name . "; lastSiblingElement: " . ($this->lastSiblingElement === null ? "none" : $this->lastSiblingElement->name));
            //error_log("Zone is contiguous: " . $zone->isContiguous);
            
            if(property_exists($zone, "elements")) {
                //error_log("Zone has elements");
                $elements = $this->getElementsFromZone($zone);
                if(count($elements) === 0) {
                    //error_log("Not creating line for zone " . $zone->name . " because no elements or all duplicates");

                    // Still need to set lastSibling just in case next segment links to a duplicate
                    if($isParentContiguous && count($zone->elements) > 0) {
                        $this->lastSiblingElement = $zone->elements[count($zone->elements) - 1];              
                    }

                    return;
                }
                $name = $zone->name;
                $displayName = $name;
                $description = $zone->description;
                $displayName = $this->formatTitle($displayName);
                $description = $this->formatTitle($description, 48);
                $line = new Line($name, $displayName, $description, $elements, $this->isCluster, $this->isProperties, $this->isPaginate, $this->cedHostname, $this->isLinkCed);
                array_push($lines, $line);
                if($isParentContiguous && $this->lastSiblingElement !== null) {
                    //error_log("Setting contiguous tip: " . $elements[0]->name . " to parent " . $this->lastSiblingElement->name);
                    $this->tipToParentMap[$elements[0]->name] = $this->lastSiblingElement->name;    
                } else if(count($zone->elements) > 1) {
                    // Branches are defined with a duplicate element at the start so we just grab all possible candidates
                    //error_log("Setting branch tip candidate: " . $zone->elements[1]->name . " to parent " . $zone->elements[0]->name);
                    $this->tipToParentMap[$zone->elements[1]->name] = $zone->elements[0]->name;
                    
                    // Now handle case when we have one or more duplicates at the begining
                    /*$i = 1;
                    while(count($zone->elements) > $i && array_key_exists($zone->elements[$i]->name, $this->tipToParentMap)) {
                        $i++;
                    }

                    if($i > 1 && count($zone->elements) > $i) {
                        error_log("Setting link: " . $zone->elements[$i]->name . " to " . $zone->elements[$i - 1]->name);
                        $this->tipToParentMap[$zone->elements[$i]->name] = $zone->elements[$i - 1]->name;
                    }*/
                    if(count($elements) > 1) {
                        $first = $elements[0];
                        if($first->name !== $zone->elements[0]->name) {
                            $index = false;
                            for($i = 0; $i < count($zone->elements); $i++) {
                                if($zone->elements[$i]->name === $first->name) {
                                    $index = $i;
                                    break;
                                } 
                            } 
                            if($index !== false) {
                                $this->tipToParentMap[$zone->elements[$index]->name] = $zone->elements[$index - 1]->name;  
                            } 
                        }
                    }
                }

                if($isParentContiguous && count($zone->elements) > 0) {
                    $this->lastSiblingElement = $zone->elements[count($zone->elements) - 1];
                    //error_log("Setting last sibling element: " . $this->lastSiblingElement->name);
                }
            } else if(property_exists($zone, "subzones")) {
                //error_log("Zone has subzones");
                foreach($zone->subzones as $subzone) {
                    if(!$zone->isContiguous) {
                        //error_log("Clearing last sibling element");
                        $this->lastSiblingElement = null;
                    }

                    $this->addLineFromZoneRecursive($subzone, $lines, $zone->isContiguous);
                }
            } else {
                error_log("Zone has neither elements or subzones");
            }
    }
 
    private function loadLinesFromHierarchy($zone) {
        $this->resetBookKeepingVars();

        $lines = array();

        $this->addLineFromZoneRecursive($zone, $lines);
 
        return $lines;
    }

    private function resetBookKeepingVars() {
        $this->tipToParentMap = array();
        $this->tailToParentMap = array();
        $this->duplicatesMap = array();
        $this->lastSiblingElement = null;

        // We just hard code for now
        $this->tailToParentMap["MBH3I03V"] = "MDS1I01"; // GUN3 Line
        $this->tailToParentMap["HallAElectronDetector"] = "MCP1P04"; // HallACompton Line
        $this->tailToParentMap["IPM3P03"] = "MMC3P04"; // HallCCompton Line
    }

    private function loadZoneHierarchyFromJson($zoneName, $workspaceName) {
        $url = "https://" . $this->cedHostname . "/zones/" . $zoneName . "?out=json&p=NameAlias,SegMask,Unpowered,ModeledAs,S";

        if($workspaceName !== null) {
            $url = $url . "&wrkspc=" . $workspaceName;
        }

        $jsonStr = file_get_contents($url);

        if($jsonStr === false) {
            throw new \Exception("Unable to query CED: " . error_get_last()["message"]); // print_r(error_get_last(), true)); //implode(PHP_EOL, error_get_last()));
        }

        $jsonObj = json_decode($jsonStr);

        return $jsonObj;
    }

    private function loadElementsFromCommandLine($zone, $workspace) {
        //if($_SERVER['DEBUG_YN'] == 'Y') {
        //    error_log('Fetching elements');
        //}

        putenv("ORACLE_HOME=/usr/csite/comtools/oracle/app/oracle/product/PRO");

        $wrkspcArg = "";

        if($workspace != null) {
            error_log("Using Workspace " . $workspace);
            $wrkspcArg = "-wrkspc " . escapeshellarg($workspace);
        }

        //$start = microtime(true);
        exec("/usr/csite/certified/bin/ced " . $wrkspcArg . " -inventory -z" . escapeshellarg($zone) . " -r -sS -pS SegMask Unpowered -f1 2>&1", $output, $return_var);
        //$end = microtime(true);

        //$diff = ($end - $start);

        //error_log("Fetching elements took: " . number_format($diff * 1000, 2) . " milliseconds");

        if($return_var != 0) {
            throw new \Exception('Unable to execute command: ' . implode(PHP_EOL, $output));
        }

        /*if($_SERVER['DEBUG_YN'] == 'Y') {
            error_log('Command output: ');
            foreach($output as $line) {
                error_log($line . PHP_EOL);
            }
        }*/

        array_shift($output);

        array_pop($output);

        $elements = array();

        foreach($output as $line) {
            $tokens = preg_split('/\s+/', $line);

            $element = new Element();

            $element->name = $tokens[0];
            $element->type = $tokens[1];
            $element->s = $tokens[2];
            // tokens[3] is s units of "meters"
            $element->segments = array();

            $segmentsAndAreas = explode("+", $tokens[4]);

            foreach($segmentsAndAreas as $candidate) {
                if(substr($candidate, 0, 1) === "S") {
                    array_push($element->segments, $candidate);
                }
            }
          
            if(array_key_exists(5, $tokens)) { 
                $element->unpowered = ($tokens[5] == "1");
            }
 
            array_push($elements, $element);
        }

        return $elements;
    }

    private function determineLine($element) {
        $lineName;

        if($element->name == "MCP1P01" || $element->name == "MCP1P04" || $element->name == "MVS1P01V" || $element->name == "MVS1P04V") {
            $lineName = "S_hallA";
        } else if($element->name == "MVS3P01V" || $element->name == "MMC3P01") {
            $lineName = "S_hallC";
        } else if($element->name == "IBP5H01H" || $element->name == "MPE5H02") {
            $lineName = "S_hallD";
        } else if($element->name == "ITV2C00") {
            $lineName = "S_Bsy_Dump";
        } else if(count($element->segments) == 0) {
            error_log("No Segment: " . $element->name);
            $lineName = "S_Unknown";
        } else if(count($element->segments) == 2) {
            if(in_array("S_Bsy_Dump", $element->segments) && in_array("S_hallC", $element->segments)) {
                $lineName = "S_Bsy_Dump";
            } else if(in_array("S_bsyA", $element->segments) && in_array("S_acc", $element->segments)) {
                $lineName = "S_bsyA";
            } else if(in_array("S_bsy2", $element->segments) && in_array("S_acc", $element->segments)) {
                $lineName = "S_bsy2";
            } else if(in_array("S_hallACompton", $element->segments) && in_array("S_hallA", $element->segments)) {
                $lineName = "S_hallACompton";
            } else if(in_array("S_hallCCompton", $element->segments) && in_array("S_hallC", $element->segments)) {
                $lineName = "S_hallCCompton";
            } else if (in_array("S_hallD", $element->segments) && in_array("S_hallD_photon", $element->segments)) {
                $lineName = "S_hallD_photon";
            } else {
                $lineName = array_values($element->segments)[0];
            }
        } else {
            $lineName = array_values($element->segments)[0];
        }

        return $lineName;
    }

    private function organizeIntoLines($elements) {
        $lineMap = array();

        foreach($elements as $element) {
            $lineName = $this->determineLine($element);

            if(array_key_exists($lineName, $lineMap)) {
                $lineElements = $lineMap[$lineName];
                array_push($lineElements, $element);
                $lineMap[$lineName] = $lineElements;
                //error_log("Line " . $lineName . " has " . count($lineElements) . " elements now");
            } else {
                //error_log("Missing Beam Line for element: " . $element->name . "; Beam Line Name: " .  $lineName);
                $lineElements = array();
                array_push($lineElements, $element);
                $lineMap[$lineName] = $lineElements;
            }
        }

        $lineUtil = LineUtility::getInstance();

        $lines = array();

        foreach($lineMap as $lineName => $elements) {
            $displayName = $lineUtil->getCommonSegmentName($lineName);

            $line = new Line($lineName, $displayName, null, $elements, $this->isCluster, $this->isProperties, $this->isPaginate, $this->cedHostname, $this->isLinkCed);
            $lines[$lineName] = $line;
        }

        $this->manuallyAssignConnections();

        return $lines;
    }

    private function lineCompare($a, $b) {
        if($a->getElements()[0]->s == $b->getElements()[0]->s) {
            return 0;
        }

        return ($a->getElements()[0]->s < $b->getElements()[0]->s) ? -1 : 1;
    }

    private function laneCompare($a, $b) {
        if($a->getLines()[0]->getElements()[0]->s == $b->getLines()[0]->getElements()[0]->s) {
            return 0;
        }

        return ($a->getLines()[0]->getElements()[0]->s < $b->getLines()[0]->getElements()[0]->s) ? -1 : 1;
    }


    private function allParallelLinesOrganization($lines) {
        $lanes = array();

        $i = 0;
        foreach($lines as $line) {
            if(count($line->getElements()) > 0) {
                $laneLines = array();
                array_push($laneLines, $line);
                $offsets = array(0);
                $lane = new Lane($laneLines, $offsets, null, $i++);
                array_push($lanes, $lane);
            }
        }

        // Now sort by s coordinate
        usort($lanes, array($this, "laneCompare"));

        return $lanes;
    }

    private function getLaneThatHasLine($lanes, $line) {
        $theOne = null;

        foreach($lanes as $lane) {
            foreach($lane->getLines() as $l) {
                if($l->getName() == $line->getName()) {
                    //error_log("Found lane containing line " . $line->getName());
                    $theOne = $lane;
                    break;
                }
            }
        }

        return $theOne;
    }

    private function manuallyAssignConnections() {
        $this->tipToParentMap["MAD3D00H"] = "MDL0L02"; // Mott
        $this->tipToParentMap["MBH5D00H"] = "MDL0L02"; // Bubble Chamber
        $this->tipToParentMap["IPM2D00"] = "MDL0L02"; // 2D 5MeV
        $this->tipToParentMap["ITV4D00"] = "MBF0L06"; // 4D 123 MeV
        $this->tipToParentMap["MBD2C00V"] = "ITV2C00"; // Hall B
        $this->tipToParentMap["MZB1C02"] = "ITV2C00"; // Hall A
        $this->tipToParentMap["VRV5H02"] = "IBP5H01H"; // Hall D Photon
        $this->tipToParentMap["MYBBS04"] = "MYR9S05"; // Hall D
        $this->tipToParentMap["MJF3C04"] = "VTC3C03A"; // Hall C
        $this->tipToParentMap["VIP3C19A"] = "MMC3P01"; // Hall C Compton
        $this->tipToParentMap["IPM2S00"] = "MXR2S01"; // BSY2
    }

    private function handleLineWithTipParent($line, & $lanes, $parentLine, $parentElementName, & $alreadyAddedToLane) {
        $linePlacedIntoLane = false;
        $parentLane = $this->getLaneThatHasLine($lanes, $parentLine);
        if($parentLane != null) {
            $isLastLineInLane = $parentLane->isLastLine($parentLine);
        } else {
            $isLastLineInLane = true;
        }

        if($isLastLineInLane && ($parentLine->getElements()[count($parentLine->getElements()) - 1]->name == $parentElementName)) {
            //error_log("This is a continuation");
            if($parentLane != null) {
                //error_log("Found existing continuation lane with starting line: " . $theLane->getLines()[0]->getName());
                $parentLane->addLine($line);
                array_push($alreadyAddedToLane, $line->getName());
                $linePlacedIntoLane = true;
                $parentLine->isContinuation = true;
            } else {
                error_log("Could not find existing lane to place continuation line");
            }
        } else {
            //error_log("This is a branch");
            $line->isBranch = true;
            $offset = 0;
            
            if($parentLane != null) {
                $offset = $parentLane->getElementOffset($parentElementName);
                if($this->isPaginate) {
                    $pageOffset = $parentLane->getElementPageOffset($parentElementName);
                    //error_log("Setting page offset: " . $pageOffset);
                } else {
                    $pageOffset = null;
                }
            }
            
            $laneLines = array();
            array_push($laneLines, $line);
            $offsets = array($offset);
            $pageOffsets = array($pageOffset);
            //error_log("Creating new lane with index: " . count($lanes));
            $lane = new Lane($laneLines, $offsets, $pageOffsets, count($lanes));
            array_push($lanes, $lane);
            array_push($alreadyAddedToLane, $line->getName());
            $linePlacedIntoLane = true;
            //$line->lane = $lane;
            //error_log("Assigning Lane Offset: " . $laneOffset);
            $branchLines = array();
            
            if(array_key_exists($parentElementName, $parentLine->branchParentElements)) {
                $branchLines = $parentLine->branchParentElements[$parentElementName];
            }
      
            array_push($branchLines, $line);
            $parentLine->branchParentElements[$parentElementName] = $branchLines;
            //error_log("Adding branch tip element: " . $parentElementName);
        }

        return $linePlacedIntoLane;
    }

    private function handleLineWithTailParent($line, & $lanes, $parentLine, $parentElementName) {
        $parentLane = $this->getLaneThatHasLine($lanes, $parentLine);
        if($parentLane != null) {
            if($parentLane->getFirstLineName() != $line->lane->getFirstLineName()) {
                 //error_log("joining tail line for lanes: " . $parentLane->getFirstLineName() . " to " . $line->lane->getFirstLineName());
                 $joinLines = array();

                 if(array_key_exists($parentElementName, $parentLine->joinParentElements)) {
                     $joinLines = $parentLine->joinParentElements[$parentElementName];
                 }

                 array_push($joinLines, $line);
                 $parentLine->joinParentElements[$parentElementName] = $joinLines;

                 $parentLine->recalculateHeightForJoins();
                 $parentLine->lane->recalculateHeightForJoins();
            } else {
                error_log("tail parent and child are already in same lane");
            }
        } else {
            error_log("Could not find existing lane for tail parent line");
        }
    }

    private function printLaneOrder($lanes) {
        foreach($lanes as $lane) {
            error_log($lane->getFirstLineName());
        }
    }

    private function connectedLinesOrganization($lines) {
        $lanes = array();

        // Now sort by s coordinate
        usort($lines, array($this, "lineCompare"));

        $alreadyAddedToLane = array();

        // Connect lanes
        foreach($lines as $line) {

            if(in_array($line->getName(), $alreadyAddedToLane)) {
                continue;
            }

            $linePlacedIntoLane = false;
            $tipElementName = $line->getElements()[0]->name;
            //error_log("Looking for tip element: " . $tipElementName);
            if(array_key_exists($tipElementName, $this->tipToParentMap)) {
                $parentElementName = $this->tipToParentMap[$tipElementName];
                //error_log("Found!, now looking for parent: " . $parentElementName);
                foreach($lines as $parentLine) {
                    foreach($parentLine->getElements() as $element) {
                        if($parentElementName == $element->name) {
                            //error_log("Found a tip to parent match: " . $parentElementName . " -> " . $tipElementName);
                            //error_log("Checking if: " . $otherLine->getElements()[count($otherLine->getElements()) - 1]->name . " equals " . $parentElementName);
                            $linePlacedIntoLane = $this->handleLineWithTipParent($line, $lanes, $parentLine, $parentElementName, $alreadyAddedToLane);
                            break;
                        }
                    }
                }
            }

            if(!$linePlacedIntoLane) {
                //error_log("Line " . $line->getName() . " is a parallel line");
                $laneLines = array();
                array_push($laneLines, $line);
                $offsets = array(0);
                //error_log("Creating new lane with index: " . count($lanes));
                $lane = new Lane($laneLines, $offsets, null, count($lanes));
                array_push($lanes, $lane);
                array_push($alreadyAddedToLane, $line->getName());
            }
        }

        // Now handle tail joins (only if not paginated)
        if(!$this->isPaginate) {
            foreach($lines as $line) {
                $tailElementName = $line->getElements()[count($line->getElements()) - 1]->name;
                //error_log("Looking for tail element: " . $tailElementName);
                if(array_key_exists($tailElementName, $this->tailToParentMap)) {
                    $parentElementName = $this->tailToParentMap[$tailElementName];
                    //error_log("Found!, now looking for parent: " . $parentElementName);
                    foreach($lines as $parentLine) {
                        foreach($parentLine->getElements() as $element) {
                            if($parentElementName == $element->name) {
                                //error_log("Found a tail to parent match: " . $parentElementName . " -> " . $tailElementName);
                                //error_log("Checking if: " . $parentLine->getElements()[count($parentLine->getElements()) - 1]->name . " equals " . $parentElementName);
                                $this->handleLineWithTailParent($line, $lanes, $parentLine, $parentElementName);
                                break;
                            }
                        }
                    }
                }
            }
        }

        // Now sort again to organize left-branches correctly
        //error_log("Order Before: ");
        //$this->printLaneOrder($lanes);
        $leftLanes = array();
        $rightLanes = array();
        $this->breakoutLanes($lanes, $leftLanes, $rightLanes);
        
        if(count($leftLanes) > 1) {
            array_unshift($leftLanes, null);
            $leftLanes = $this->consolidateLanesByStacking($leftLanes);
            array_shift($leftLanes);
        }

        if(count($rightLanes) > 2) {
            $rightLanes = $this->consolidateLanesByStacking($rightLanes);
        }

        $lanes = array_merge($leftLanes, $rightLanes);

        for($i = 0; $i < count($lanes); $i++) {
            $lanes[$i]->setLaneIndex($i);
        }

        //error_log("Order After: ");
        //$this->printLaneOrder($lanes);

        return $lanes;
    }

    private function breakoutLanes($lanes, & $leftLanes, & $rightLanes) {
        foreach($lanes as $lane) {
            $firstElementName = $lane->getFirstElementName();
            if(in_array($firstElementName, $this->leftLineTipElements)) {
                array_push($leftLanes, $lane);
            } else {
                array_push($rightLanes, $lane);
            }
        }
    }

    private function consolidateLanesByStacking($lanes) {
        $consolidatedLanes = array();

        for($i = 0; $i < min(2, count($lanes)); $i++) {
            $lane = $lanes[$i];
            array_push($consolidatedLanes, $lane);
        }

        // Only lanes 3+ can be collapsed into lanes 2+
        for($i = 2; $i < count($lanes); $i++) {
            //error_log('Examining lane: ' . $i);
            $lane = $lanes[$i];
            $lines = $lane->getLines();

            $moved = false;

            if(count($lines) === 1) {
                $line = $lines[0];

                if($line->isBranch) {
                    for($j = 1; $j < $i; $j++) {
                        if(!$lanes[$j]->isStacked && !$lanes[$j]->isCollision($line->interval)) {
                            //error_log("Found a collapse spot; lane: " . $j . "; setting line " . $line->getName() . " lane index: " . $j);
                            $lanes[$j]->addStackedLane($lane);
                            $line->lane = $lanes[$j];
                            $moved = true;
                            break;
                        } else {
                            //error_log("Can't collapse on lane: " . $j . "; sorry line " . $line->getName());
                        }
                    }
                }
            }
          
            if(!$moved) {
                array_push($consolidatedLanes, $lane);
            }
        }

        // This might not be needed since we do it later
        for($i = 1; $i < count($consolidatedLanes); $i++) {
            $consolidatedLanes[$i]->setLaneIndex($i);
        }

        return $consolidatedLanes;
    }

    private function organizeIntoLanes($lines) {
        $lanes;

        if($this->isConnect) {
            $lanes = $this->connectedLinesOrganization($lines);
        } else {
            $lanes = $this->allParallelLinesOrganization($lines);
        }

        return $lanes;
    }
}
?>
