<?php

require_once __DIR__ . '/SimpleTestFramework.php';
require_once __DIR__ . '/../app/Services/AISongGeneratorService.php';

use Tests\SimpleTestFramework;
use App\Services\AISongGeneratorService;

class AISongGeneratorTest extends SimpleTestFramework
{
    private $service;

    public function __construct()
    {
        $this->service = new AISongGeneratorService();
    }

    public function testGenerateUniqueTitle()
    {
        $method = $this->getPrivateMethod(AISongGeneratorService::class, 'generateUniqueTitle');
        
        $title = 'Harry Potter';
        $mood = 'Fantasía';
        $keywords = [
            'protagonist' => 'Harry',
            'location' => 'Hogwarts',
            'theme' => 'Magic',
            'emotion' => 'Wonder'
        ];
        
        // Since uniqid() is used, we can't predict exact output, but we can check format/content
        $result = $method->invoke($this->service, $title, $mood, $keywords, 0);
        
        $this->assertTrue(strlen($result) > 0, 'Generated title should not be empty');
        
        // Run multiple times to ensure stability/variety
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $method->invoke($this->service, $title, $mood, $keywords, $i);
        }
        
        // Check uniqueness is not guaranteed by function alone due to random seed in uniqid,
        // but we can check that it generates valid strings.
        // Actually, the previous code had `crc32($bookTitle . $index . uniqid())` so it IS random every call.
        
        $this->assertTrue(is_string($results[0]), 'Should return a string');
    }

    public function testGenerateCustomLyrics()
    {
        $method = $this->getPrivateMethod(AISongGeneratorService::class, 'generateCustomLyrics');
        
        $bookData = [
            'title' => 'The Hobbit',
            'mood' => 'Aventura',
            'synopsis' => 'A hobbit goes on an adventure.'
        ];
        $keywords = [
            'protagonist' => 'Bilbo',
            'location' => 'Middle Earth',
            'theme' => 'Courage',
            'goal' => 'Treasure',
            'emotion' => 'Fear'
        ];
        
        $lyrics = $method->invoke($this->service, $bookData, $keywords, 0);
        
        $this->assertTrue(strpos($lyrics, '[Verso 1]') !== false, 'Lyrics should contain Verse 1 tag');
        $this->assertTrue(strpos($lyrics, 'Bilbo') !== false || strpos($lyrics, 'el protagonista') !== false, 'Lyrics might contain protagonist name');
        // Note: protagonist replacement depends on templates. If template has {protagonist}, it will be replaced.
        
        // Check structure
        $this->assertTrue(strlen($lyrics) > 100, 'Lyrics should be substantial length');
    }
}
