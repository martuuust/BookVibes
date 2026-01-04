<?php

require_once __DIR__ . '/SimpleTestFramework.php';
require_once __DIR__ . '/../app/Services/CharacterGenerator.php';

use Tests\SimpleTestFramework;
use App\Services\CharacterGenerator;

class CharacterGeneratorTest extends SimpleTestFramework
{
    private $generator;

    public function __construct()
    {
        $this->generator = new CharacterGenerator();
    }

    public function testIsValidCharacterName()
    {
        $method = $this->getPrivateMethod(CharacterGenerator::class, 'isValidCharacterName');
        
        // Valid names
        $this->assertTrue($method->invoke($this->generator, 'Harry Potter'), 'Harry Potter should be valid');
        $this->assertTrue($method->invoke($this->generator, 'Katniss Everdeen'), 'Katniss should be valid');
        $this->assertTrue($method->invoke($this->generator, 'Jon Snow'), 'Jon Snow should be valid');

        // Invalid names (Blacklist/UI terms)
        $this->assertFalse($method->invoke($this->generator, 'Home'), 'Home should be invalid');
        $this->assertFalse($method->invoke($this->generator, 'Search'), 'Search should be invalid');
        $this->assertFalse($method->invoke($this->generator, 'Login'), 'Login should be invalid');
        $this->assertFalse($method->invoke($this->generator, 'Navigation'), 'Navigation should be invalid');
        $this->assertFalse($method->invoke($this->generator, 'Goodreads'), 'Goodreads should be invalid');
        $this->assertFalse($method->invoke($this->generator, 'Kindle'), 'Kindle should be invalid');
        
        // Invalid structure
        $this->assertFalse($method->invoke($this->generator, ''), 'Empty string should be invalid');
        $this->assertFalse($method->invoke($this->generator, 'the'), 'Lowercase stopword should be invalid');
    }

    public function testExtractTraits()
    {
        $method = $this->getPrivateMethod(CharacterGenerator::class, 'extractTraits');
        
        // Visual traits
        $desc = "He was a tall man with blue eyes and a scar on his forehead.";
        $traits = $method->invoke($this->generator, $desc);
        $this->assertContains('tall', $traits, 'Should extract tall');
        // 'blue eyes' is now in the list, so it should be extracted as 'blue eyes'
        $this->assertContains('blue eyes', $traits, 'Should extract blue eyes');
        $this->assertContains('scar', $traits, 'Should extract scar');

        // Roles
        $desc2 = "She is the protagonist of the story, a young wizard.";
        $traits2 = $method->invoke($this->generator, $desc2);
        $this->assertContains('protagonist', $traits2, 'Should extract protagonist');
        $this->assertContains('wizard', $traits2, 'Should extract wizard');
        $this->assertContains('young', $traits2, 'Should extract young');
    }
    
    public function testExtractTraitsGenreFallback()
    {
        $method = $this->getPrivateMethod(CharacterGenerator::class, 'extractTraits');
        
        // No visual traits, fallback to genre
        $desc = "A mysterious character who lurks in the shadows.";
        $traits = $method->invoke($this->generator, $desc, 'Horror');
        
        // "shadowy" might be in appearance list? No, checked code: 'shadowy' is in fallback for horror.
        // Let's check code again. 'shadowy' is NOT in $appearance array, but IS in the fallback.
        // Wait, 'shadowy' is added in lines 1576.
        
        $this->assertContains('pale', $traits, 'Horror fallback should include pale');
        $this->assertContains('shadowy', $traits, 'Horror fallback should include shadowy');
        
        // Fantasy fallback
        $traitsFantasy = $method->invoke($this->generator, "A brave warrior.", 'Fantasy');
        $this->assertContains('medieval clothing', $traitsFantasy, 'Fantasy fallback should include medieval clothing');
    }

    public function testCoherentMerge()
    {
        $method = $this->getPrivateMethod(CharacterGenerator::class, 'coherentMerge');
        
        $d1 = "He is a good boy.";
        $d2 = "He is a good boy. He loves magic.";
        
        $merged = $method->invoke($this->generator, $d1, $d2);
        
        // Should remove duplicate sentences
        $this->assertTrue(substr_count($merged, 'He is a good boy') === 1, 'Should deduplicate sentences');
        $this->assertContains('He loves magic', $merged);
    }
}
