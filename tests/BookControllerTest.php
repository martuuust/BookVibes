<?php

require_once __DIR__ . '/SimpleTestFramework.php';
require_once __DIR__ . '/../app/autoload.php';

use Tests\SimpleTestFramework;
use App\Controllers\BookController;

class BookControllerTest extends SimpleTestFramework
{
    private $controller;

    public function __construct()
    {
        // We might need to mock Database if Controller constructor uses it, but it seems it doesn't.
        // However, if Controller constructor calls session_start, we might have issues.
        // Let's check Core\Controller.
        // Assuming it's safe to instantiate for now.
        $this->controller = new BookController();
    }

    public function testGetFallbackTracks()
    {
        $method = $this->getPrivateMethod(BookController::class, 'getFallbackTracks');

        // Test Mood: Romántico
        $tracks = $method->invoke($this->controller, 'Romántico');
        $this->assertTrue(count($tracks) > 0, 'Should return tracks for Romántico');
        $this->assertEquals('John Legend', $tracks[0]['artist'], 'First track for Romántico should be John Legend');

        // Test Mood: Misterio
        $tracksMisterio = $method->invoke($this->controller, 'Misterio');
        $this->assertTrue(count($tracksMisterio) > 0, 'Should return tracks for Misterio');
        $this->assertEquals('Billie Eilish', $tracksMisterio[0]['artist'], 'First track for Misterio should be Billie Eilish');

        // Test Default Fallback (Unknown Mood)
        $tracksDefault = $method->invoke($this->controller, 'UnknownMood');
        $this->assertTrue(count($tracksDefault) > 0, 'Should return default tracks');
        $this->assertEquals('Harry Styles', $tracksDefault[0]['artist'], 'Default track should be Harry Styles');
    }
}
