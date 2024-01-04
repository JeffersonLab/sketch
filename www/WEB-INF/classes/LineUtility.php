<?php
namespace sketch;

class LineUtility {
    private static $instance;
    private $typeToSymbolMap;
    private $typeToCommonTypeMap;
    private $segmaskToCommonNameMap;

    private function __construct() {
        // Private constructor

        $this->typeToSymbolMap = array();
        $this->typeToSymbolMap['BrockCavity'] = "#brock-cavity";
        $this->typeToSymbolMap['EarthFieldCoil'] = "#coil";
        $this->typeToSymbolMap['StringCoil'] = "#coil";
        $this->typeToSymbolMap['HorizontalCorrector'] = "#horizontal-corrector";
        $this->typeToSymbolMap['VerticalCorrector'] = "#vertical-corrector";
        $this->typeToSymbolMap['HorizontalKicker'] = "#horizontal-kicker";
        $this->typeToSymbolMap['VerticalKicker'] = "#vertical-kicker";
        $this->typeToSymbolMap['IonChamber'] = "#ion-chamber";
        $this->typeToSymbolMap['Puck'] = "#puck";
        $this->typeToSymbolMap['Diffuser'] = "#diffuser";
        $this->typeToSymbolMap['BeamLossMonitor'] = "#beam-loss-monitor";
        $this->typeToSymbolMap['BPM'] = "#position-monitor";
        $this->typeToSymbolMap['ButtonBPM'] = "#position-monitor";
        $this->typeToSymbolMap['HaloMonitor'] = "#halo-monitor";
        $this->typeToSymbolMap['PiraniGauge'] = "#pirani-gauge";
        $this->typeToSymbolMap['BayertAlbertPiraniGauge'] = "#pirani-gauge";
        $this->typeToSymbolMap['ThermocoupleGauge'] = "#thermocouple-gauge";
        $this->typeToSymbolMap['FaradayCup'] = "#faraday-cup";
        $this->typeToSymbolMap['Collimator'] = "#collimator";
        $this->typeToSymbolMap['Viewer'] = "#viewer";
        $this->typeToSymbolMap['SLM'] = "#viewer";
        $this->typeToSymbolMap['VideoOnly'] = "#viewer";
        $this->typeToSymbolMap['RTIMirror'] = "#viewer";
        $this->typeToSymbolMap['IonPump'] = "#ion-pump";
        $this->typeToSymbolMap['VacuumPump'] = "#vacuum-pump";
        $this->typeToSymbolMap['VacuumValve'] = "#beamline-vacuum-valve";
        $this->typeToSymbolMap['TurboPump'] = "#turbo-pump";
        $this->typeToSymbolMap['HighPowerDump'] = "#high-power-dump";
        $this->typeToSymbolMap['LowPowerDump'] = "#high-power-dump";
        $this->typeToSymbolMap['ColdCathodeGauge'] = "#cathode-gauge";
        $this->typeToSymbolMap['InsertableDump'] = "#beam-stopper";
        $this->typeToSymbolMap['BeamStopper'] = "#beam-stopper";
        $this->typeToSymbolMap['Quadrupole'] = "#quadrupole";
        $this->typeToSymbolMap['Dipole'] = "#dipole";
        $this->typeToSymbolMap['Sextupole'] = "#sextupole";
        $this->typeToSymbolMap['Aperture'] = "#aperture";
        $this->typeToSymbolMap['WarmCavity'] = "#prebuncher";
        $this->typeToSymbolMap['YaoCavity'] = "#yao-cavity";
        $this->typeToSymbolMap['Harp'] = "#harp";
        $this->typeToSymbolMap['BCM'] = "#beam-current-monitor";
        $this->typeToSymbolMap['Solenoid'] = "#solenoid";
        $this->typeToSymbolMap['WienFilter'] = "#wien-filter";
        $this->typeToSymbolMap['Target'] = "#target";
        $this->typeToSymbolMap['RFSeparator'] = "#rf-separator";
        $this->typeToSymbolMap['ThinSepta'] = "#thin-septa";
        $this->typeToSymbolMap['CryoCavity'] = "#cryo-cavity";
        $this->typeToSymbolMap['Detector'] = "#detector";
        $this->typeToSymbolMap['TargetLadder'] = "#target-ladder";
        $this->typeToSymbolMap['PairSpectrometer'] = "#pair-spectrometer";
        $this->typeToSymbolMap['Radiator'] = "#radiator";
        $this->typeToSymbolMap['PermanentMagnet'] = "#permanent-magnet";
        $this->typeToSymbolMap['BeamProfiler'] = "#beam-profiler";
        $this->typeToSymbolMap['Goniometer'] = "#goniometer";
        $this->typeToSymbolMap['Octupole'] = "#octupole";
        $this->typeToSymbolMap['LASER'] = "#laser";
        $this->typeToSymbolMap['ElectroOpticCell'] = "#eye";
        $this->typeToSymbolMap["EmittanceScanner"] = "#emittance-scanner";
        $this->typeToSymbolMap['Mark'] = "#mark";
        $this->typeToSymbolMap['ThinWindow'] = "#glasses";
        $this->typeToSymbolMap['PairSpecMagnet'] = "#magnet";
        $this->typeToSymbolMap['PairSpecConverter'] = "#map-marker";
        $this->typeToSymbolMap['Polarimeter'] = "#speedometer";
        $this->typeToSymbolMap['SweepMagnet'] = "#broom";

        $this->typeToCommonTypeMap = array();
        $this->typeToCommonTypeMap["Standard"] = "Viewer";
        $this->typeToCommonTypeMap["YAG"] = "Viewer";
        $this->typeToCommonTypeMap["OTR"] = "Viewer";
        $this->typeToCommonTypeMap["Quad"] = "Quadrupole";
        $this->typeToCommonTypeMap["QA"] = "Quadrupole";
        $this->typeToCommonTypeMap["QI"] = "Quadrupole";
        $this->typeToCommonTypeMap["QK"] = "Quadrupole";
        $this->typeToCommonTypeMap["QR"] = "Quadrupole";
        $this->typeToCommonTypeMap["QE"] = "Quadrupole";
        $this->typeToCommonTypeMap["QW"] = "Quadrupole";
        $this->typeToCommonTypeMap["QU"] = "Quadrupole";
        $this->typeToCommonTypeMap["QS"] = "Quadrupole";
        $this->typeToCommonTypeMap["QT"] = "Quadrupole";
        $this->typeToCommonTypeMap["QJ"] = "Quadrupole";
        $this->typeToCommonTypeMap["QD"] = "Quadrupole";
        $this->typeToCommonTypeMap["QB"] = "Quadrupole";
        $this->typeToCommonTypeMap["QL"] = "Quadrupole";
        $this->typeToCommonTypeMap["QN"] = "Quadrupole";
        $this->typeToCommonTypeMap["QP"] = "Quadrupole";
        $this->typeToCommonTypeMap["QF"] = "Quadrupole";
        $this->typeToCommonTypeMap["QC"] = "Quadrupole";
        $this->typeToCommonTypeMap["QX"] = "Quadrupole";
        $this->typeToCommonTypeMap["QZ"] = "Quadrupole";
        $this->typeToCommonTypeMap["BA"] = "Dipole";
        $this->typeToCommonTypeMap["BP"] = "Dipole";
        $this->typeToCommonTypeMap["BE"] = "Dipole";
        $this->typeToCommonTypeMap["BJ"] = "Dipole";
        $this->typeToCommonTypeMap["BQ"] = "Dipole";
        $this->typeToCommonTypeMap["BY"] = "Dipole";
        $this->typeToCommonTypeMap["BZ"] = "Dipole";
        $this->typeToCommonTypeMap["CP"] = "Dipole";
        $this->typeToCommonTypeMap["CV"] = "Dipole";
        $this->typeToCommonTypeMap["CW"] = "Dipole";
        $this->typeToCommonTypeMap["CX"] = "Dipole";
        $this->typeToCommonTypeMap["GG"] = "Dipole";
        $this->typeToCommonTypeMap["GU"] = "Dipole";
        $this->typeToCommonTypeMap["GV"] = "Dipole";
        $this->typeToCommonTypeMap["GQ"] = "Dipole";
        $this->typeToCommonTypeMap["GY"] = "Dipole";
        $this->typeToCommonTypeMap["GW"] = "Dipole";
        $this->typeToCommonTypeMap["GX"] = "Dipole";
        $this->typeToCommonTypeMap["JD"] = "Dipole";
        $this->typeToCommonTypeMap["JI"] = "Dipole";
        $this->typeToCommonTypeMap["JG"] = "Dipole";
        $this->typeToCommonTypeMap["JH"] = "Dipole";
        $this->typeToCommonTypeMap["XJ"] = "Dipole";
        $this->typeToCommonTypeMap["XK"] = "Dipole";
        $this->typeToCommonTypeMap["MC"] = "Dipole";
        $this->typeToCommonTypeMap["XA"] = "Dipole";
        $this->typeToCommonTypeMap["XB"] = "Dipole";
        $this->typeToCommonTypeMap["XC"] = "Dipole";
        $this->typeToCommonTypeMap["XD"] = "Dipole";
        $this->typeToCommonTypeMap["XE"] = "Dipole";
        $this->typeToCommonTypeMap["XN"] = "Dipole";
        $this->typeToCommonTypeMap["XO"] = "Dipole";
        $this->typeToCommonTypeMap["XW"] = "Dipole";
        $this->typeToCommonTypeMap["XQ"] = "Dipole";
        $this->typeToCommonTypeMap["XI"] = "Dipole";
        $this->typeToCommonTypeMap["XH"] = "Dipole";
        $this->typeToCommonTypeMap["XU"] = "Dipole";
        $this->typeToCommonTypeMap["XV"] = "Dipole";
        $this->typeToCommonTypeMap["XY"] = "Dipole";
        $this->typeToCommonTypeMap["XM"] = "Dipole";
        $this->typeToCommonTypeMap["XF"] = "Dipole";
        $this->typeToCommonTypeMap["BW"] = "Dipole";
        $this->typeToCommonTypeMap["BX"] = "Dipole";
        $this->typeToCommonTypeMap["JA"] = "Dipole";
        $this->typeToCommonTypeMap["JC"] = "Dipole";
        $this->typeToCommonTypeMap["XS"] = "Dipole";
        $this->typeToCommonTypeMap["ZA"] = "Dipole";
        $this->typeToCommonTypeMap["YB"] = "Dipole";
        $this->typeToCommonTypeMap["YR"] = "Dipole";
        $this->typeToCommonTypeMap["ZB"] = "Dipole";
        $this->typeToCommonTypeMap["JF"] = "Dipole";
        $this->typeToCommonTypeMap["XR"] = "Dipole";
        $this->typeToCommonTypeMap["XT"] = "Dipole";
        $this->typeToCommonTypeMap["XG"] = "Dipole";
        $this->typeToCommonTypeMap["XL"] = "Dipole";
        $this->typeToCommonTypeMap["XZ"] = "Dipole";
        $this->typeToCommonTypeMap["XP"] = "Dipole";
        $this->typeToCommonTypeMap["TG"] = "Dipole";
        $this->typeToCommonTypeMap["XX"] = "Dipole";
        $this->typeToCommonTypeMap["AccStyle"] = "Harp";
        $this->typeToCommonTypeMap["HallB-PMT"] = "Harp";
        $this->typeToCommonTypeMap["SuperHarp"] = "Harp";
        $this->typeToCommonTypeMap["SEEBPM"] = "BPM";
        $this->typeToCommonTypeMap["nABPM"] = "BPM";
        $this->typeToCommonTypeMap["DRBPM"] = "BPM";
        $this->typeToCommonTypeMap["DUMPBPM"] = "BPM";
        $this->typeToCommonTypeMap["4ChBPM"] = "BPM";
        $this->typeToCommonTypeMap["EBPM"] = "BPM";
        $this->typeToCommonTypeMap["LOGBPM"] = "BPM";
        $this->typeToCommonTypeMap["BeamlineValve"] = "VacuumValve";
        $this->typeToCommonTypeMap["RoughingValve"] = "VacuumValve";
        $this->typeToCommonTypeMap["ManualValve"] = "VacuumValve";
        $this->typeToCommonTypeMap["FastValve"] = "VacuumValve";
        $this->typeToCommonTypeMap["ColdCathode"] = "ColdCathodeGauge";
        $this->typeToCommonTypeMap["2KWDump"] = "InsertableDump";
        $this->typeToCommonTypeMap["DPPump"] = "VacuumPump";
        $this->typeToCommonTypeMap["HCorrector"] = "HorizontalCorrector";
        $this->typeToCommonTypeMap["VCorrector"] = "VerticalCorrector";
        $this->typeToCommonTypeMap["HDiagKicker"] = "HorizontalKicker"; 
        $this->typeToCommonTypeMap["VDiagKicker"] = "VerticalKicker";


        $this->segmaskToCommonNameMap = array();
        $this->segmaskToCommonNameMap["S_acc"] = "Accelerator";
        $this->segmaskToCommonNameMap["S_Inj_5D"] = "Bubble Chamber (5D)";
        $this->segmaskToCommonNameMap["S_5MeV_Mott"] = "Mott (3D) Dump";
        $this->segmaskToCommonNameMap["S_5MeV_Spect"] = "5MeV (2D) Dump";
        $this->segmaskToCommonNameMap["S_Inj_Spect"] = "123MeV (4D) Dump";
        $this->segmaskToCommonNameMap["S_500KeV_Spect"] = "500KeV (1D)";
        $this->segmaskToCommonNameMap["S_hallA"] = "Hall A";
        $this->segmaskToCommonNameMap["S_hallB"] = "Hall B";
        $this->segmaskToCommonNameMap["S_hallC"] = "Hall C";
        $this->segmaskToCommonNameMap["S_hallD"] = "Hall D";
        $this->segmaskToCommonNameMap["S_hallACompton"] = "Hall A Compton";
        $this->segmaskToCommonNameMap["S_hallCCompton"] = "Hall C Compton";
        $this->segmaskToCommonNameMap["S_hallD_photon"] = "Hall D Photon";
        $this->segmaskToCommonNameMap["S_Bsy_Dump"] = "BSY Dump";
        $this->segmaskToCommonNameMap["S_bsy2"] = "BSY First Pass (2nd Arc)";
        $this->segmaskToCommonNameMap["S_bsy4"] = "BSY Second Pass (4th Arc)";
        $this->segmaskToCommonNameMap["S_bsy6"] = "BSY Third Pass (6th Arc)";
        $this->segmaskToCommonNameMap["S_bsy8"] = "BSY Fourth Pass (8th Arc)";
        $this->segmaskToCommonNameMap["S_bsyA"] = "BSY Fifth Pass (10th Arc)";
        $this->segmaskToCommonNameMap["S_Unknown"] = "Unspecified";
    }


    public static function getInstance() {
        if(null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function getSymbolNameForType($type) {
        $symbolName;

        if(array_key_exists($type, $this->typeToSymbolMap)) {
            $symbolName = $this->typeToSymbolMap[$type];
        } else {
            $symbolName = "#default-element";
        }

        return $symbolName;
    }


    public function getCommonType($element) {
        $value = $element->type;

        if(array_key_exists($element->type, $this->typeToCommonTypeMap)) {
            $value = $this->typeToCommonTypeMap[$element->type];
        }

        return $value;
    }

    public function getCommonSegmentName($segmentName) {
        $value = $segmentName;

        if(array_key_exists($segmentName, $this->segmaskToCommonNameMap)) {
            $value = $this->segmaskToCommonNameMap[$segmentName];
        }

        return $value;
    }

}
?>
