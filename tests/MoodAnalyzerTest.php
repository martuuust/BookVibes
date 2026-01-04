<?php

require_once __DIR__ . '/SimpleTestFramework.php';
require_once __DIR__ . '/../app/autoload.php';

use Tests\SimpleTestFramework;
use App\Services\MoodAnalyzer;

class MoodAnalyzerTest extends SimpleTestFramework
{
    private $analyzer;

    public function __construct()
    {
        $this->analyzer = new MoodAnalyzer();
    }

    public function testAnalyzeMoodOnly()
    {
        // Test Romantic
        $data = [
            'synopsis' => 'Una historia de amor verdadero y pasión.',
            'keywords' => ['romance', 'boda']
        ];
        $result = $this->analyzer->analyzeMoodOnly($data);
        $this->assertEquals('Romántico', $result['mood'], 'Should detect Romantic mood');

        // Test Mystery
        $dataMys = [
            'synopsis' => 'El detective investiga un crimen oscuro.',
            'keywords' => ['asesinato', 'pistas']
        ];
        $resultMys = $this->analyzer->analyzeMoodOnly($dataMys);
        $this->assertEquals('Intriga y Suspenso', $resultMys['mood'], 'Should detect Mystery mood');
        
        // Test Neutral/Default
        $dataNeutral = [
            'synopsis' => 'Un libro sobre programación PHP.',
            'keywords' => ['código', 'computadora']
        ];
        $resultNeutral = $this->analyzer->analyzeMoodOnly($dataNeutral);
        $this->assertEquals('Neutral', $resultNeutral['mood'], 'Should default to Neutral');
    }
}
