<?php namespace test;

use PHPUnit\Framework\TestCase;
use WitPact\Utility\Watcher;

/**
 * 
 */
final class WatcherTest extends TestCase {
    /**
     * 
     */
    public function testGetTarget() {
        $package = new class {
            public function getExtra() {
                return [
                    'installer-paths' => [
                        'path/{$name}' => ['type:wordpress-theme'],
                    ],
                ];
            }
            public function getName() {
                return 'vendor/name';
            }
            public function getType() {
                return 'witpact-theme';
            }
        };
        $watcher = new Watcher;
        $target = $watcher->getTarget($package);
        $result = 'path/name';
        $this->assertEquals($target, $result);
    }
}