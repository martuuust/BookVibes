<?php

namespace Tests;

class SimpleTestFramework
{
    private $passed = 0;
    private $failed = 0;

    public function run()
    {
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (strpos($method, 'test') === 0) {
                echo "Running $method... ";
                try {
                    $this->$method();
                    echo "\033[32mPASSED\033[0m\n";
                    $this->passed++;
                } catch (\Exception $e) {
                    echo "\033[31mFAILED\033[0m: " . $e->getMessage() . "\n";
                    $this->failed++;
                }
            }
        }
    }

    protected function assertTrue($condition, $message = 'Condition is not true')
    {
        if (!$condition) {
            throw new \Exception($message);
        }
    }

    protected function assertFalse($condition, $message = 'Condition is not false')
    {
        if ($condition) {
            throw new \Exception($message);
        }
    }

    protected function assertEquals($expected, $actual, $message = '')
    {
        if ($expected !== $actual) {
            $msg = $message ? $message : "Expected " . json_encode($expected) . ", got " . json_encode($actual);
            throw new \Exception($msg);
        }
    }

    protected function assertContains($needle, $haystack, $message = '')
    {
        if (is_array($haystack)) {
            if (!in_array($needle, $haystack)) {
                throw new \Exception($message ? $message : "Array does not contain '$needle'");
            }
        } elseif (is_string($haystack)) {
            if (strpos($haystack, $needle) === false) {
                throw new \Exception($message ? $message : "String does not contain '$needle'");
            }
        }
    }

    public function getResults()
    {
        return ['passed' => $this->passed, 'failed' => $this->failed];
    }
    
    protected function getPrivateMethod($className, $methodName) {
        $reflector = new \ReflectionClass($className);
        $method = $reflector->getMethod($methodName);
        $method->setAccessible(true);
        return $method;
    }
}
