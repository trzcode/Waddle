<?php

namespace Waddle\Parsers;

use Exception;
use SimpleXMLElement;
use Waddle\Activity;
use Waddle\Lap;
use Waddle\Parser;
use Waddle\TrackPoint;

class TCXParser extends Parser
{
    const NS_TRAININGCENTER_V2 = 'http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2';
    const NS_ACTIVITY_EXTENSION_V2 = 'http://www.garmin.com/xmlschemas/ActivityExtension/v2';

    /** @var string */
    private $nameNSActivityExtensionV2;

    /**
     * Parse the TCX file
     * @param string $pathname
     * @return Activity
     * @throws Exception
     */
    public function parse($pathname)
    {
        // Check that the file exists
        $this->checkForFile($pathname);

        // Create a new activity instance
        $activity = new Activity();

        // Load the XML in the TCX file
        $data = simplexml_load_file($pathname);
        if (!isset($data->Activities->Activity)) {
            throw new Exception("Unable to find valid activity in file contents");
        }
        $this->detectsNamespace($data);

        $data->registerXPathNamespace('ns3', self::NS_ACTIVITY_EXTENSION_V2);
        $data->registerXPathNamespace('a', self::NS_TRAININGCENTER_V2);

        // Parse the first activity
        $activityNode = $data->Activities->Activity[0];
        $activity->setStartTime(new \DateTime((string)$activityNode->Id));
        $activity->setType((string)$activityNode['Sport']);

        // Now parse the laps
        // There should only be 1 lap, but they are stored in an array just in case this ever changes
        foreach ($activityNode->Lap as $lapNode) {
            $activity->addLap($this->parseLap($lapNode));
        }

        // Finally return the activity object
        return $activity;
    }

    /**
     *
     * @var SimpleXMLElement $xml
     */
    private function detectsNamespace(SimpleXMLElement $xml)
    {
        $this->nameNSActivityExtensionV2 = null;

        $namespaces = $xml->getNamespaces(true);
        foreach ($namespaces as $name => $ns) {
            if ($ns === self::NS_ACTIVITY_EXTENSION_V2) {
                $this->nameNSActivityExtensionV2 = $name;
            }
        }
    }

    /**
     * Parse the lap XML
     * @param SimpleXMLElement $lapNode
     * @return Lap
     * @throws Exception
     */
    protected function parseLap($lapNode)
    {
        $lap = new Lap();
        $lap->setTotalTime((float)$lapNode->TotalTimeSeconds);
        $lap->setTotalDistance((float)$lapNode->DistanceMeters);
        $lap->setMaxSpeed((float)$lapNode->MaximumSpeed);
        $lap->setTotalCalories((float)$lapNode->Calories);

        if (isset($lapNode->AverageHeartRateBpm)) {
            $lap->setAvgHeartRate((int)$lapNode->AverageHeartRateBpm->Value);
        }

        if (isset($lapNode->MaximumHeartRateBpm)) {
            $lap->setMaxHeartRate((int)$lapNode->MaximumHeartRateBpm->Value);
        }

        // For Bike Activity
        if (isset($lapNode->Cadence)) {
            $lap->setCadence((int)$lapNode->Cadence);
            $lap->setAvgCadence((int)$lapNode->Cadence);
        }
        // Parse ActivityLapExtension
        $this->registerXPathNamespaces($lapNode);
        
        $maxCadences = $lapNode->xpath('a:Extensions/ns3:TPX/ns3:MaxBikeCadence/text()');
        
        if (isset($maxCadences) && \is_array($maxCadences) && \count($maxCadences) == 1) {
            $lap->setMaxCadence((int)$maxCadences[0]);
        }

        $maxCadences = $lapNode->xpath('a:Extensions/ns3:TPX/ns3:MaxRunCadence/text()');
        
        if (isset($maxCadences) && \is_array($maxCadences) && \count($maxCadences) == 1) {
            $lap->setMaxCadence((int)$maxCadences[0]);
        }

        $avgCadences = $lapNode->xpath('a:Extensions/ns3:TPX/ns3:AvgRunCadence/text()');
        
        if (isset($avgCadences) && \is_array($avgCadences) && \count($avgCadences) == 1) {
            $lap->setAvgCadence((int)$avgCadences[0]);
            $lap->setCadence((int)$avgCadences[0]);
        }

        $avgSpeed = $lapNode->xpath('a:Extensions/ns3:TPX/ns3:AvgSpeed/text()');
            
        if (isset($avgSpeed) && \is_array($avgSpeed) && \count($avgSpeed) == 1) {
            $lap->setAvgSpeed((float)$avgSpeed[0]);
        }

        // Watts
        $avgWatts = $lapNode->xpath('a:Extensions/ns3:TPX/ns3:AvgWatts/text()');
        
        if (isset($avgWatts) && \is_array($avgWatts) && \count($avgWatts) == 1) {
            $lap->setAvgWatts((int)$avgWatts[0]);
        }
        $maxWatts = $lapNode->xpath('a:Extensions/ns3:TPX/ns3:MaxWatts/text()');
        
        if (isset($maxWatts) && \is_array($maxWatts) && \count($maxWatts) == 1) {
            $lap->setMaxWatts((int)$maxWatts[0]);
        }
        $steps = $lapNode->xpath('a:Extensions/ns3:TPX/ns3:Steps/text()');
        
        if (isset($steps) && \is_array($steps) && \count($steps) == 1) {
            $lap->setSteps((int)$steps[0]);
        }

        // Loop through tracks
        foreach($lapNode->Track as $trackNode)
        {
            // Loop through the track points of a track
            foreach($trackNode->Trackpoint as $trackPointNode)
            {
                $lap->addTrackPoint($this->parseTrackPoint($trackPointNode));
            }
        }

        return $lap;
    }

    /**
     * Parse the XML of a track point
     * @param SimpleXMLElement $trackPointNode
     * @return TrackPoint
     * @throws Exception
     */
    protected function parseTrackPoint($trackPointNode)
    {
        $point = new TrackPoint();
        $point->setTime(new \DateTime((string)$trackPointNode->Time));
        $point->setPosition([
            'lat' => (float)$trackPointNode->Position->LatitudeDegrees,
            'lon' => (float)$trackPointNode->Position->LongitudeDegrees,
        ]);
        $point->setAltitude((float)$trackPointNode->AltitudeMeters);
        $point->setDistance((float)$trackPointNode->DistanceMeters);

        // If heartrate is present on node, set that.
        if (isset($trackPointNode->HeartRateBpm)) {
            if (isset($trackPointNode->HeartRateBpm->Value)) {
                $point->setHeartRate((int)$trackPointNode->HeartRateBpm->Value);
            }
        }

        // If cadence is present on node, set that.
        // In case the activity is a Bike activity
        if (isset($trackPointNode->Cadence)) {
            $point->setCadence((int)$trackPointNode->Cadence);
        }

        // If the speed extension is present on the node, set that.
        if ($this->nameNSActivityExtensionV2) {
            $this->registerXPathNamespaces($trackPointNode);

            $speed = $trackPointNode->xpath('a:Extensions/ns3:TPX/ns3:Speed/text()');
            
            if (isset($speed) && \is_array($speed) && \count($speed) == 1) {
                $point->setSpeed((float)$speed[0]);
            }

            // Watts
            $watts = $trackPointNode->xpath('a:Extensions/ns3:TPX/ns3:Watts/text()');
            
            if (isset($watts) && \is_array($watts) && \count($watts) == 1) {
                $point->setWatts((int)$watts[0]);
            }

            // In case the activity is a Running activity
            $runCadence = $trackPointNode->xpath('a:Extensions/ns3:TPX/ns3:RunCadence/text()');
            
            if (isset($runCadence) && \is_array($runCadence) && \count($runCadence) == 1) {
                $point->setCadence((int)$runCadence[0]);
            }
        }
        

        return $point;

    }

    private function registerXPathNamespaces(&$node): void {
        $node->registerXPathNamespace('a', self::NS_TRAININGCENTER_V2);
        $node->registerXPathNamespace('ns3', self::NS_ACTIVITY_EXTENSION_V2);
    }
}