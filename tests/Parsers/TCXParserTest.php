<?php

namespace Waddle\Parsers;

class TCXParserTest extends \PHPUnit\Framework\TestCase
{
    public $parser;
    
    public function setUp(): void {
        $this->parser = new \Waddle\Parsers\TCXParser();
    }

    public function testDetectsNamespace() {
        $reflector = new \ReflectionClass($this->parser );
		$method = $reflector->getMethod('detectsNamespace');
		$method->setAccessible(true);
        $method->invokeArgs($this->parser, [simplexml_load_file(__DIR__ . '/../run_garmin.tcx')]);

        $reflector = new \ReflectionClass($this->parser);
		$property = $reflector->getProperty('nameNSActivityExtensionV2');
		$property->setAccessible(true);
 
		$this->assertEquals('ns3', $property->getValue($this->parser)); 
    }

    public function testDetectsNamespaceSeveralParse() {
        $reflector = new \ReflectionClass($this->parser );
		$method = $reflector->getMethod('detectsNamespace');
		$method->setAccessible(true);

        $reflector = new \ReflectionClass($this->parser);
        $property = $reflector->getProperty('nameNSActivityExtensionV2');
        
        $method->invokeArgs($this->parser, [simplexml_load_file(__DIR__ . '/../run_garmin.tcx')]);
		$property->setAccessible(true);
        $this->assertEquals('ns3', $property->getValue($this->parser)); 
        
        $method->invokeArgs($this->parser, [simplexml_load_file(__DIR__ . '/../run.tcx')]);
		$property->setAccessible(true);
		$this->assertEquals('x', $property->getValue($this->parser)); 
    }

    private function getActivity() {
        return $this->parser->parse( __DIR__ . '/../run.tcx' );
    }
    
    public function testActivityLaps(){
        $this->assertEquals(1, count($this->getActivity()->getLaps()));
    }
    
    public function testActivityTotalDistance(){
        $this->assertEquals(4824.94, $this->getActivity()->getTotalDistance());
    }
    
    public function testActivityTotalDuration(){
        $this->assertEquals(1424, $this->getActivity()->getTotalDuration());
    }
    
    public function testActivityAveragePacePerMile(){
        $this->assertEquals('00:07:54', $this->getActivity()->getAveragePacePerMile());
    }
    
    public function testActivityAveragePacePerKilometre(){
        $this->assertEquals('00:04:55', $this->getActivity()->getAveragePacePerKilometre());
    }
    
    public function testActivityAverageSpeedMPH(){
        $this->assertEquals('7.58', round($this->getActivity()->getAverageSpeedInMPH(), 2));
    }
    
    public function testActivityAverageSpeedKPH(){
        $this->assertEquals('12.20', round($this->getActivity()->getAverageSpeedInKPH(), 2));
    }
    
    public function testActivityTotalCalories(){
        $this->assertEquals(372, $this->getActivity()->getTotalCalories());
    }
    
    public function testActivityMaxSpeedMPH(){
        $this->assertEquals('10.45', round($this->getActivity()->getMaxSpeedInMPH(), 2));
    }
    
    public function testActivityMaxSpeedKPH(){
        $this->assertEquals('16.81', round($this->getActivity()->getMaxSpeedInKPH(), 2));
    }
    
    public function testActivityTotalAscent(){
        $result = $this->getActivity()->getTotalAscentDescent();
        $this->assertEquals(50.9, $result['ascent']);
    }
    
    public function testActivityTotalDescent(){
        $result = $this->getActivity()->getTotalAscentDescent();
        $this->assertEquals(50.2, $result['descent']);
    }
    
    public function testActivitySplitsInMiles(){
        $this->assertEquals(3, count($this->getActivity()->getSplits('mi')));
    }
    
    public function testActivitySplitsInKilometres(){
        $this->assertEquals(5, count($this->getActivity()->getSplits('k')));
    }

    public function testTrackPointsRunCadenceWithNullValues() {
        foreach ($this->getActivity()->getLaps() as $lap) {
            foreach ($lap->getTrackPoints() as $tp) {
                $this->assertNull($tp->getCadence());
            }
        }
    }

    public function testTrackPointsSpeedWithNullValues() {
        foreach ($this->getActivity()->getLaps() as $lap) {
            foreach ($lap->getTrackPoints() as $tp) {
                $this->assertNull($tp->getSpeed());
            }
        }
    }

    public function testTrackPointsRunCadence() {
        $cadences = [
            70,93,86,85,85,93,89,87,88,88,88,82,87,87,86,86,87,85,88,88,88,87,87,86,89,88,88,87,84,88,88,88,88,87,85,84,88,88,88,87,86,81,88,88,88,88,87,86,85,88,88,88,87,84,89,89,88,89,88,88,84,86,87,88,88,88,88,87,87,84,90,89,90,88,89,88,87,89,88,89,90,87,82,87,87,89,87,83,87,88,88,87,86,89,87,89,89,88,85,87,88,88,88,86,91,90,91,88,85,91,88,89,88,87,44,83,87,86,100,87,85,85,87,88,90,86,88,54,81,83,89,88,87,85,83,92,89,89,91,85,70,87,91,91,89,0,0
        ];
        $activity = $this->parser->parse( __DIR__ . '/../run_garmin.tcx' );

        $trackpoints = [];
        foreach ($activity->getLaps() as $lap) {
            foreach ($lap->getTrackPoints() as $tp) {
                $trackpoints[] = $tp;
            }
        }

        $this->assertEquals(count($cadences), count($trackpoints));
        for ($i = 0; $i < count($cadences); $i++) {
            $this->assertEquals($cadences[$i], $trackpoints[$i]->getCadence());
        }
    }

    public function testTrackPointsSpeed() {
        $speeds = [
            1.184999942779541,1.184999942779541,2.8269999027252197,2.7990000247955322,2.305000066757202,2.13700008392334,2.2950000762939453,2.4820001125335693,2.509999990463257,2.5290000438690186,2.5940001010894775,2.6500000953674316,2.249000072479248,2.378999948501587,2.5940001010894775,2.621999979019165,2.621999979019165,2.6029999256134033,2.434999942779541,2.3610000610351562,2.378999948501587,2.500999927520752,2.6029999256134033,2.621999979019165,2.4070000648498535,2.444999933242798,2.61299991607666,2.6500000953674316,2.6410000324249268,2.575000047683716,2.4070000648498535,2.4630000591278076,2.4730000495910645,2.565999984741211,2.6029999256134033,2.6029999256134033,2.378999948501587,2.3610000610351562,2.3889999389648438,2.4730000495910645,2.5380001068115234,2.5380001068115234,2.5290000438690186,2.4820001125335693,2.4630000591278076,2.4630000591278076,2.5290000438690186,2.556999921798706,2.565999984741211,2.500999927520752,2.5190000534057617,2.565999984741211,2.63100004196167,2.6500000953674316,2.63100004196167,2.6029999256134033,2.5940001010894775,2.5940001010894775,2.5940001010894775,2.621999979019165,2.63100004196167,2.6029999256134033,2.5850000381469727,2.63100004196167,2.5290000438690186,2.5190000534057617,2.8459999561309814,2.818000078201294,2.8369998931884766,2.8369998931884766,1.809999942779541,2.994999885559082,2.9760000705718994,2.938999891281128,2.930000066757202,2.9579999446868896,1.875,2.7249999046325684,2.9670000076293945,2.9760000705718994,2.9579999446868896,2.9579999446868896,2.9670000076293945,2.818000078201294,2.7899999618530273,2.7990000247955322,2.8550000190734863,2.8269999027252197,2.5190000534057617,2.369999885559082,2.4820001125335693,2.565999984741211,2.6029999256134033,2.6689999103546143,2.687000036239624,2.7809998989105225,2.7899999618530273,2.8369998931884766,2.7809998989105225,2.6410000324249268,2.4630000591278076,2.5290000438690186,2.621999979019165,2.743000030517578,2.7149999141693115,2.7060000896453857,2.818000078201294,2.938999891281128,2.994999885559082,2.9760000705718994,2.9670000076293945,3.0230000019073486,3.0789999961853027,3.0880000591278076,2.994999885559082,2.7899999618530273,2.6410000324249268,2.546999931335449,2.556999921798706,2.565999984741211,2.565999984741211,2.565999984741211,2.546999931335449,2.5290000438690186,2.490999937057495,2.509999990463257,2.556999921798706,2.575000047683716,2.575000047683716,2.565999984741211,2.9019999504089355,3.2279999256134033,3.2660000324249268,3.1440000534057617,3.0980000495910645,1.6419999599456787,3.23799991607666,2.9210000038146973,3.0880000591278076,3.115999937057495,3.125999927520752,2.7899999618530273,2.7060000896453857,2.8929998874664307,3.0230000019073486,3.0139999389648438,2.994999885559082
        ];
        $activity = $this->parser->parse( __DIR__ . '/../run_garmin.tcx' );
        
        $trackpoints = [];
        foreach ($activity->getLaps() as $lap) {
            foreach ($lap->getTrackPoints() as $tp) {
                $trackpoints[] = $tp;
            }
        }

        $this->assertEquals(count($speeds), count($trackpoints));
        for ($i = 0; $i < count($speeds); $i++) {
            $this->assertEquals($speeds[$i], $trackpoints[$i]->getSpeed());
        }
    }
    
}