<?php
namespace sketch;

class Diagram {
    public static $HEADER_HEIGHT = 100;
    private $name;
    private $lanes;
    private $width;
    private $height;
    private $numberOfPages;
    private $currentPageIndex = 0;
    private $isPaginate;

    /**
     *@internal
     *
     * Creates a new Diagram (should only be called by DiagramFactory) 
     * 
     * @param string $name the Diagram name
     * $param string $description Diagram description or null if none
     * @param array $lanes the array of lanes that make up the diagram
     * @param integer $numberOfPages the number of pages needed to draw this diagram (always 1 if not paginated)
     * @param boolean $isPaginate true if paginated display
     */
    public function __construct($name, $description, $lanes, $numberOfPages, $isPaginate) {
        $this->name = $name;
        $this->description = $description;
        $this->lanes = $lanes;
        $this->numberOfPages = $numberOfPages;
        $this->isPaginate = $isPaginate;

        $this->width = 0;
        $this->height = 0;

        if($isPaginate) {
            $this->width = Line::$PAGE_WIDTH;
        } else {
            foreach($lanes as $lane) {
                $this->width = $this->width + $lane->getWidth();
            }
        }

        if(count($lanes) == 0) {
            throw new \Exception("Zone has no lanes");
        }

        $tallestLane = $lanes[0];

        for($i = 1; $i < count($lanes); $i++) {
            if($tallestLane->getHeight() < $lanes[$i]->getHeight()) {
                $tallestLane = $lanes[$i];
            }
        }

        //error_log("Tallest Lane (first line): " . $tallestLane->getLines()[0]->getName());

        $this->height = $tallestLane->getHeight();
    } 

    private function hasMorePages() {
        return $this->currentPageIndex < ($this->numberOfPages);
    }

    private function drawNextPage() {
        $ECHO_INDENT = "        ";
        $x = 0;
        $y = 0;

        echo $ECHO_INDENT . '<div>' . PHP_EOL;
        $ECHO_INDENT = $ECHO_INDENT . "    ";
        echo $ECHO_INDENT . '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 ' . Line::$PAGE_WIDTH . ' ' . Line::$PAGE_HEIGHT . '" width="' . Line::$PAGE_WIDTH . '" height="' . Line::$PAGE_HEIGHT . '">' . PHP_EOL;
        $ECHO_INDENT = $ECHO_INDENT . "    ";
        echo $ECHO_INDENT . '<title>' . $this->name . ' Page ' . ($this->currentPageIndex + 1) . '</title>' . PHP_EOL;

        $scriptdir = dirname(__FILE__);
        require $scriptdir . '/../includes/svgstyle.inc';
// Must include symbols directly due to various browser bugs like this one in Chrome: https://bugs.chromium.org/p/chromium/issues/detail?id=109212       
require $scriptdir . '/../includes/symbols.inc'; 

        echo $ECHO_INDENT . '<rect class="background" x="0" y="0" width="' . Line::$PAGE_WIDTH . '" height="' . Line::$PAGE_HEIGHT . '" fill="#F0F8FF" stroke-width="0"/>' . PHP_EOL;
        
        if($this->currentPageIndex == 0) { // FIRST PAGE
            $y = self::$HEADER_HEIGHT;
            echo $ECHO_INDENT . '<text class="header" x="' . (Line::$PAGE_WIDTH / 2) . '" y="30" font-size="20" font-weight="bold" text-anchor="middle">' . encode($this->getName()) . '</text>' . PHP_EOL;

            if($this->description !== null) {
                echo $ECHO_INDENT . '<text class="header" x="' . (Line::$PAGE_WIDTH / 2) . '" y="50" font-size="16" font-style="italic" text-anchor="middle">(' . encode($this->getDescription()) . ')</text>' . PHP_EOL;
            }
        }

        foreach($this->lanes as $lane) {
            $lane->drawPage($this->currentPageIndex, $x, $y, $ECHO_INDENT);
            $x = $x + $lane->getWidth();
        }

        if($this->currentPageIndex == ($this->numberOfPages - 1)) { // LAST PAGE
            $this->drawFootnote((Line::$PAGE_HEIGHT - 72), $ECHO_INDENT);
        }

        $ECHO_INDENT = substr($ECHO_INDENT, 0, strlen($ECHO_INDENT) - 4);
        echo $ECHO_INDENT . '</svg>' . PHP_EOL;
        $ECHO_INDENT = substr($ECHO_INDENT, 0, strlen($ECHO_INDENT) - 4);
        echo $ECHO_INDENT . '</div>' . PHP_EOL;

        $this->currentPageIndex++;
    }

    /**
     * Draws the diagram, which is either a single poster sized svg or a set of svgs in divs if the diagram is paginated.
     *
     */
    public function draw() {
        if($this->isPaginate) {
            while($this->hasMorePages()) {
                $this->drawNextPage();
            }
            $this->currentPageIndex = 0; // Reset in-case we want to draw again
        } else {
            $this->drawPoster();
        }
    }

    private function drawPoster() {
        $ECHO_INDENT = "    ";
        $x = 0;
        $y = self::$HEADER_HEIGHT;

        echo '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 ' . $this->getWidth() . " " . $this->getHeight() . '" preserveAspectRatio="xMinYMin meet" width="' . $this->getWidth() . '" height="' . $this->getHeight() . '">' . PHP_EOL;
        echo $ECHO_INDENT . '<title>' . $this->getName() . '</title>' . PHP_EOL;

        $scriptdir = dirname(__FILE__);
        require $scriptdir . '/../includes/svgstyle.inc';
        require $scriptdir . '/../includes/symbols.inc';

        echo $ECHO_INDENT . '<rect class="background" x="0" y="0" width="' . $this->getWidth() . '" height="' . $this->getHeight() . '" fill="#F0F8FF" stroke-width="0"/>' . PHP_EOL; 
        echo $ECHO_INDENT . '<text class="header" x="' . ($this->getWidth() / 2) . '" y="30" font-size="20" font-weight="bold" text-anchor="middle">' . encode($this->getName()) . '</text>' . PHP_EOL;

        if($this->description !== null) {
            echo $ECHO_INDENT . '<text class="header" x="' . ($this->getWidth() / 2) . '" y="50" font-size="16" font-style="italic" text-anchor="middle">(' . encode($this->getDescription()) . ')</text>' . PHP_EOL;
        }

        foreach($this->lanes as $lane) {
            $lane->draw($x, $y);
            $x = $x + $lane->getWidth();
        }

        $this->drawFootnote(($this->getHeight() - 72), $ECHO_INDENT);

        echo '</svg>' . PHP_EOL;
    }

    private function drawFootnote($y, $ECHO_INDENT) {
        echo $ECHO_INDENT . '<text class="footnote" y="' . $y . '" font-size="12" font-style="italic" text-anchor="start">' . PHP_EOL;
        $ECHO_INDENT = $ECHO_INDENT . "    ";
        echo $ECHO_INDENT . '<tspan x="10" dy="0" font-weight="bold">Notes:</tspan>' . PHP_EOL;
        echo $ECHO_INDENT . '<tspan x="10" dy="20">1. Not to scale</tspan>' . PHP_EOL;
        echo $ECHO_INDENT . '<tspan x="10" dy="20">2. S in meters</tspan>' . PHP_EOL;
        echo $ECHO_INDENT . '<tspan x="10" dy="20">3. * Unpowered, &#8224; Not Modeled</tspan>' . PHP_EOL;
        $ECHO_INDENT = substr($ECHO_INDENT, 0, strlen($ECHO_INDENT) - 4);
        echo $ECHO_INDENT . '</text>' . PHP_EOL;

        $x = $this->getWidth() - 10;

        echo $ECHO_INDENT . '<text class="gennote" x="' . $x . '" y="' . ($y + 60) . '" font-size="12" font-style="italic" text-anchor="end">' . PHP_EOL;
        $ECHO_INDENT = $ECHO_INDENT . "    ";
        echo $ECHO_INDENT . '<tspan dy="0" font-weight="bold">Generated:</tspan>' . PHP_EOL;
        date_default_timezone_set("America/New_York");
        echo $ECHO_INDENT . '<tspan dy="0">' . date("d-M-Y") . '</tspan>' . PHP_EOL;
        $ECHO_INDENT = substr($ECHO_INDENT, 0, strlen($ECHO_INDENT) - 4);
        echo $ECHO_INDENT . '</text>' . PHP_EOL;
    }

    public function getName() {
        return $this->name;
    }
   
    public function getDescription() {
        return $this->description;
    }

    public function getWidth() {
        return $this->width;
    }

    public function getHeight() {
        return $this->height;
    }

    public function isPaginate() {
        return $this->isPaginate;
    }
}
?>
