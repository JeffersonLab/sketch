<?php
namespace sketch;

class Lane {
    private $lines;
    private $offsets;
    private $pageOffsets;
    private $laneIndex; // Because of stacking some lanes have same index!
    private $width;
    private $height;
    private $globalToLocalLineMap = array();
    private $globalToLocalOffsetMap = array();
    private $stackedLanes = array();
    public $isStacked = false;

    public function __construct($lines, $offsets, $pageOffsets, $laneIndex) {
        $this->lines = $lines;
        $this->offsets = $offsets;
        $this->pageOffsets = $pageOffsets;
        $this->laneIndex = $laneIndex;

        $this->calculateDimensions();
        $this->calculateGlobalToLocalMaps();

        foreach($lines as $line) {
            $line->lane = $this;
        }
    }

    private function calculateGlobalToLocalMaps() {
        $globalPageIndex = 0;


        // THIS SETS OFFSETS FOR BRANCHES
        if($this->pageOffsets !== null && count($this->pageOffsets) > 0 && $this->pageOffsets[0] !== null) {
            $globalPageIndex = $this->pageOffsets[0] + 1;
            //error_log("Setting page to offset: " . $globalPageIndex);
        }

        foreach($this->lines as $line) {
            for($i = 0; $i < count($line->getPages()); $i++) {
                $this->globalToLocalLineMap[$globalPageIndex] = $line;
                $this->globalToLocalOffsetMap[$globalPageIndex] = $i;

                //error_log("Page Index " . $globalPageIndex . " should have line " . $line->getName() . " page index " . $i);

                $globalPageIndex++;
            }
        }
    }

    private function calculateDimensions() {
        $this->width = 0;
        $this->height = 0;

        // Height might start offset if we are branch
        if($this->offsets != null && count($this->offsets) > 0) {
            $this->height = $this->offsets[0];
        }

        $i = 0;
        foreach($this->lines as $line) {
            $this->height = $this->height + $line->getHeight();

            if(array_key_exists($i, $this->offsets)) {
                $start = $this->offsets[$i];
                $end = $start + $line->getHeight();
                $line->interval = new Interval($start, $end);
            }
            $i++;

            if($line->getWidth() > $this->width) {
                $this->width = $line->getWidth();
            }
        }
    }

    public function getElementPageOffset($elementName) {
        $offset = 0;

        foreach($this->lines as $line) {
            foreach($line->getPages() as $page) {
                foreach($page->elements as $element) {
                    if($elementName == $element->name) {
                        break 3;
                    }
                }
                $offset++;
            }
        }

        return $offset;
    }

    public function getElementOffset($elementName) {
        $offset = 0;

        if($this->offsets != null && count($this->offsets) > 0) {
            $offset = $this->offsets[0];
        }

        foreach($this->lines as $line) {
            $clusters = $line->getClusters();
            //error_log("Cluster count: " . count($clusters));
            foreach($clusters as $cluster) {
                $elements = $cluster->elements;
                for($i = 0; $i < count($elements); $i++) {
                    $offset = $offset + (Line::$SYMBOL_HEIGHT * 2);
                    if($elementName == $elements[$i]->name) {
                        break 3;
                    }    
                }
                $offset = $offset + Line::$CLUSTER_PADDING;
            }
            $offset = $offset + Line::$VERTICAL_PADDING - Line::$SYMBOL_HEIGHT;
        }

        $offset = $offset - Line::$CLUSTER_PADDING; // First cluster doesn't get padding 

        $offset = $offset + Line::$BRANCH_PADDING; // We want to give branch lines an angle

        return $offset;
    }

    public function addStackedLane($lane) {
        $lane->isStacked = true;
        array_push($this->stackedLanes, $lane);
    }

    public function addLine($line) {
        $offset = 0;

        // The first offset might be greater than zero as first offset may be branch
        if(count($this->offsets) > 0) {
            $offset = $this->offsets[0];
        }

        foreach($this->lines as $l) {
            $offset = $offset + $l->getHeight();
        }

        array_push($this->lines, $line);
        array_push($this->offsets, $offset);

        $this->calculateDimensions();
        $this->calculateGlobalToLocalMaps();

        $line->lane = $this;

        //error_log("Setting offset: " . $offset);

        //error_log("line count: " . count($this->lines));
        //error_log("first line element count: " . count($this->lines[0]->getElements()));
        //error_log("last line element count: " . count($this->lines[count($this->lines) - 1]->getElements()));
    }

    public function recalculateHeightForJoins() {
        $offset = 0;

        // The first offset might be greater than zero as first offset may be branch
        if(count($this->offsets) > 0) {
            $offset = $this->offsets[0];
        }
     
        if(count($this->lines) > 1) {
            $offset = $offset + $this->lines[0]->getHeight();

            for($i = 1; $i < count($this->lines); $i++) {
                $l = $this->lines[$i];
                //error_log("Setting Line " . $l->getName() . " offset to: " . $offset);
                $this->offsets[$i] = $offset;
                $offset = $offset + $l->getHeight();
            }
        }

        $this->calculateDimensions();
        $this->calculateGlobalToLocalMaps();
    }

    public function isLastLine($line) {
       $lastOne = false;
       $lineCount = count($this->lines);

        if($lineCount > 0) {
            if($this->lines[$lineCount - 1]->getName() == $line->getName()) {
                $lastOne = true; 
            }
        }

        return $lastOne;
    }

    public function getPageCount() {
        $maxPages = 0;

        if(count($this->lines) > 0) {

            if($this->lines[0]->isBranch && $this->pageOffsets != null && count($this->pageOffsets) > 0) {
                $maxPages = $this->pageOffsets[0] + 1; // Always draw first element on next page
            }

            for($i = 0; $i < count($this->lines); $i++) {
                $maxPages = $maxPages +  count($this->lines[$i]->getPages());
            }
        }

        return $maxPages; 
    }

    public function drawPage($globalPageIndex, $x, $y, $ECHO_INDENT) {
        //error_log("Lane Draw for page index: " . $globalPageIndex);
        if(array_key_exists($globalPageIndex, $this->globalToLocalLineMap)) {
            //error_log("Found line to draw!");
            $localLine = $this->globalToLocalLineMap[$globalPageIndex];
            $localIndex = $this->globalToLocalOffsetMap[$globalPageIndex];
            if($localIndex === 0 && $globalPageIndex !== 0) { // Each new line needs room for line header; first one just uses main zone header padding
                $y = $y + Line::$LINE_HEADER_HEIGHT;
            }
            $hasNextPage = false;
            if(array_key_exists($globalPageIndex + 1, $this->globalToLocalLineMap)) {
                $hasNextPage = true;
            }

            $localLine->draw($x, $y, $localIndex, $hasNextPage, $ECHO_INDENT);
        } else {
            //error_log("No line to draw");
        }

        for($i = 0; $i < count($this->stackedLanes); $i++) {
            $this->stackedLanes[$i]->drawPage($globalPageIndex, $x, $y, $ECHO_INDENT);
        }
        //$this->lines[0]->draw($x, $y, $globalPageIndex);
    }

    public function draw($x, $y) {
        for($i = 0; $i < count($this->lines); $i++) {
            $line = $this->lines[$i];
            $line->draw($x, ($y + $this->offsets[$i]));
        }

        if(count($this->stackedLanes) > 0 && $this->isStacked) {
            error_log("WARNING/ASSERTION: This lane is itself stacked, yet has other lanes stacked within;  this kind of nesting should not happen!");
        }

        for($i = 0; $i < count($this->stackedLanes); $i++) {
            $this->stackedLanes[$i]->draw($x, $y);
        }
    }

    private function intersects($a, $b) {
        return (($a->start <= $b->end) && ($b->start <= $a->end));
    } 

    public function isCollision($interval) {
       foreach($this->lines as $line) {
           if($this->intersects($interval, $line->interval)) {
               return true;
           }
       }

       for($i = 0; $i < count($this->stackedLanes); $i++) {
           if($this->stackedLanes[$i]->isCollision($interval)) {
               return true;
           }
       }

       return false;
    }

    public function getWidth() {
        return $this->width;
    }

    public function getHeight() {
        return $this->height;
    }

    public function getLines() {
        return $this->lines;
    }

    public function getLaneIndex() {
        return $this->laneIndex;
    }
  
    public function setLaneIndex($laneIndex) {
        $this->laneIndex = $laneIndex;
    }

    public function getFirstLineName() {
        return $this->lines[0]->getName();
    }
  
    public function getFirstElementName() {
        return $this->lines[0]->getElements()[0]->name; 
    }
}
?>
