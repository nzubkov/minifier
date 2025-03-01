<?php

require_once 'bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * TestCase for the FileMinifier class
 */
class FileMinifierTest extends TestCase
{
    use MinifierTestCase;
    
    /**
     * Temporary directory for test files
     */
    private $tempDir;
    
    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Create temporary directory for test files
        $this->tempDir = sys_get_temp_dir() . '/file-minifier-tests-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }
    
    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        // Remove temporary directory and all contents
        $this->recursiveRemoveDir($this->tempDir);
        
        parent::tearDown();
    }
    
    /**
     * Recursively remove a directory and its contents
     */
    private function recursiveRemoveDir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (is_dir($dir . '/' . $object)) {
                        $this->recursiveRemoveDir($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
    
    /**
     * Test PHP minification
     */
    public function testMinifyPHP()
    {
        $minifyPHP = $this->getMethod('minifyPHP');
        
        // Test 1: Basic PHP file with comments and whitespace
        $input = <<<'PHP'
<?php
// This is a comment
function test() {
    /* Multi-line
       comment */
    echo "Hello World";
    
    return true;
}
PHP;
        
        $expected = '<?php function test(){echo "Hello World";return true;}';
        $result = $minifyPHP->invokeArgs(null, [$input]);
        
        $this->assertEquals($expected, $result);
        
        // Test 2: PHP with complex syntax
        $input = <<<'PHP'
<?php
class Test {
    private $var = array(
        'key1' => 'value1',
        'key2' => 'value2'
    );
    
    public function method1() {
        if (isset($this->var['key1'])) {
            return $this->var['key1'];
        } else {
            return null;
        }
    }
}
PHP;
        
        $result = $minifyPHP->invokeArgs(null, [$input]);
        
        // Verify that the minified code doesn't contain multi-line comments or unnecessary whitespace
        $this->assertStringNotContainsString('/*', $result);
        $this->assertStringNotContainsString('//', $result);
        $this->assertStringNotContainsString('    ', $result);
    }
    
    /**
     * Test SQL minification
     */
    public function testMinifySQL()
    {
        $minifySQL = $this->getMethod('minifySQL');
        
        // Test SQL with comments and whitespace
        $input = <<<'SQL'
-- This is a SQL comment
SELECT
    id,
    name,
    email
FROM
    users
/* Multi-line
   comment */
WHERE
    status = 'active'
    AND created_at > '2020-01-01';
SQL;
        
        $expected = "SELECT id, name, email FROM users WHERE status = 'active' AND created_at > '2020-01-01';";
        $result = $minifySQL->invokeArgs(null, [$input]);
        
        $this->assertEquals($expected, $result);
    }
    
    /**
     * Test JavaScript minification
     */
    public function testMinifyJS()
    {
        $minifyJS = $this->getMethod('minifyJS');
        
        // Test JavaScript with comments and whitespace
        $input = <<<'JS'
// This is a comment
function calculateSum(a, b) {
    /* Multi-line
       comment */
    return a + b;
}

const x = 10;
const y = 20;
const sum = calculateSum(x, y);
console.log("Sum: " + sum);
JS;
        
        $result = $minifyJS->invokeArgs(null, [$input]);
        
        // Check key aspects of minification
        $this->assertStringNotContainsString('//', $result);
        $this->assertStringNotContainsString('/*', $result);
        $this->assertStringNotContainsString('    ', $result);
        $this->assertStringNotContainsString("\n", $result);
        
        // Ensure function and variables still exist
        $this->assertStringContainsString('function calculateSum(a,b)', $result);
        $this->assertStringContainsString('const x=10', $result);
        $this->assertStringContainsString('console.log("Sum:"+sum)', $result);
    }
    
    /**
     * Test CSS minification
     */
    public function testMinifyCSS()
    {
        $minifyCSS = $this->getMethod('minifyCSS');
        
        // Test CSS with comments and whitespace
        $input = <<<'CSS'
/* Header styles */
.header {
    color: #333;
    font-size: 16px;
    margin: 10px 0;
    padding: 5px;
}

/* Content styles */
.content {
    background-color: #f5f5f5;
    padding: 20px;
    border: 1px solid #ccc;
}
CSS;
        
        $result = $minifyCSS->invokeArgs(null, [$input]);
        
        // Check key aspects of minification
        $this->assertStringNotContainsString('/*', $result);
        $this->assertStringNotContainsString('    ', $result);
        
        // Ensure CSS rules still exist
        $this->assertStringContainsString('.header{', $result);
        $this->assertStringContainsString('color:#333', $result);
        $this->assertStringContainsString('.content{', $result);
        $this->assertStringContainsString('background-color:#f5f5f5', $result);
    }
    
    /**
     * Test Vue file minification
     */
    public function testMinifyVue()
    {
        $minifyVue = $this->getMethod('minifyVue');
        
        // Test Vue component with comments and whitespace
        $input = <<<'VUE'
<template>
    <div class="container">
        <h1>{{ title }}</h1>
        <p>{{ message }}</p>
    </div>
</template>

<script>
// Component script
export default {
    data() {
        return {
            title: 'Hello Vue',
            message: 'Welcome to the test'
        }
    },
    methods: {
        greet() {
            return 'Hello ' + this.title;
        }
    }
}
</script>

<style>
/* Component styles */
.container {
    padding: 20px;
    background-color: #f0f0f0;
}
h1 {
    color: #333;
}
</style>
VUE;
        
        $result = $minifyVue->invokeArgs(null, [$input]);
        
        // Check key aspects of minification
        $this->assertStringNotContainsString('    <div', $result);
        $this->assertStringContainsString('<template><div', $result);
        
        // Ensure all sections still exist
        $this->assertMatchesRegularExpression('/<template>.*<\/template>/s', $result);
        $this->assertMatchesRegularExpression('/<script>.*<\/script>/s', $result);
        $this->assertMatchesRegularExpression('/<style>.*<\/style>/s', $result);
        
        // Verify script and style content is minified
        $this->assertStringNotContainsString('// Component script', $result);
        $this->assertStringNotContainsString('/* Component styles */', $result);
    }
    
    /**
     * Test byte formatting
     */
    public function testFormatBytes()
    {
        $formatBytes = $this->getMethod('formatBytes');
        
        // Test various byte sizes
        $this->assertEquals('500 B', $formatBytes->invokeArgs(null, [500]));
        $this->assertEquals('1 KB', $formatBytes->invokeArgs(null, [1024]));
        $this->assertEquals('1.5 KB', $formatBytes->invokeArgs(null, [1536]));
        $this->assertEquals('1 MB', $formatBytes->invokeArgs(null, [1048576]));
        $this->assertEquals('2.5 MB', $formatBytes->invokeArgs(null, [2621440]));
    }
    
    /**
     * Test argument parsing
     */
    public function testParseArguments()
    {
        $parseArguments = $this->getMethod('parseArguments');
        
        // Test 1: Basic arguments
        $args = ['minify.php', 'file.php'];
        $expected = [
            'combine' => false,
            'output'  => null,
            'source'  => 'file.php',
            'laravel' => false
        ];
        
        $result = $parseArguments->invokeArgs(null, [$args]);
        $this->assertEquals($expected, $result);
        
        // Test 2: All options
        $args = ['minify.php', 'src', '--combine', '--output', 'dist/output.js', '--laravel'];
        $expected = [
            'combine' => true,
            'output'  => 'dist/output.js',
            'source'  => 'src',
            'laravel' => true
        ];
        
        $result = $parseArguments->invokeArgs(null, [$args]);
        $this->assertEquals($expected, $result);
        
        // Test 3: Short options
        $args = ['minify.php', 'src', '-c', '-o', 'dist/output.js', '-l'];
        $expected = [
            'combine' => true,
            'output'  => 'dist/output.js',
            'source'  => 'src',
            'laravel' => true
        ];
        
        $result = $parseArguments->invokeArgs(null, [$args]);
        $this->assertEquals($expected, $result);
    }
    
    /**
     * Test file counting in directory
     */
    public function testCountFiles()
    {
        $countFiles = $this->getMethod('countFiles');
        
        // Create test directory structure with files
        $testFiles = [
            'file1.php'        => '<?php echo "Test 1"; ?>',
            'file2.js'         => 'console.log("Test 2");',
            'file3.css'        => '.test { color: red; }',
            'file4.txt'        => 'This is not a supported file type',
            'subdir/file5.php' => '<?php echo "Test 5"; ?>',
            'subdir/file6.vue' => '<template><div>Test</div></template>'
        ];
        
        foreach ($testFiles as $path => $content) {
            $fullPath = $this->tempDir . '/' . $path;
            $dir = dirname($fullPath);
            
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            
            file_put_contents($fullPath, $content);
        }
        
        // Test counting supported files
        $count = $countFiles->invokeArgs(null, [$this->tempDir]);
        
        // Should find 5 files (file1.php, file2.js, file3.css, file5.php, file6.vue)
        // but not file4.txt (unsupported type)
        $this->assertEquals(5, $count);
    }
    
    /**
     * Test processing a single file
     */
    public function testProcessFile()
    {
        $processFile = $this->getMethod('processFile');
        
        // Create test PHP file
        $testFile = $this->tempDir . '/test.php';
        $content = <<<'PHP'
<?php
// This is a comment
function test() {
    /* Multi-line
       comment */
    echo "Hello World";
    
    return true;
}
PHP;
        
        file_put_contents($testFile, $content);
        
        // Process the file
        $outputPath = $this->tempDir . '/output';
        $result = $processFile->invokeArgs(null, [$testFile, $outputPath, false]);
        
        // Check the result (should return bytes saved)
        $this->assertGreaterThan(0, $result);
        
        // Check that the output file exists
        $this->assertFileExists($outputPath . DIRECTORY_SEPARATOR . 'test.php');
        
        // Check the content is minified
        $minifiedContent = file_get_contents($outputPath . DIRECTORY_SEPARATOR . 'test.php');
        $this->assertStringNotContainsString('// This is a comment', $minifiedContent);
        $this->assertStringNotContainsString('/* Multi-line', $minifiedContent);
    }
    
    /**
     * Test Laravel file pattern matching
     */
    public function testMinifyLaravelFile()
    {
        $minifyLaravelFile = $this->getMethod('minifyLaravelFile');
        
        // Set up the patterns property to a known state for testing
        $laravelPatternsProperty = $this->getProperty('laravelPatterns');
        $originalPatterns = $laravelPatternsProperty->getValue();
        
        // Create test Laravel files
        $files = [
            // Essential files
            'app/Http/Controllers/UserController.php' => '<?php namespace App\Http\Controllers; class UserController {}',
            'app/Models/User.php'                     => '<?php namespace App\Models; class User { protected $fillable=["name", "email"]; }',
            'app/Services/EmailService.php'           => '<?php namespace App\Services; class EmailService {}',
            
            // Excluded files
            'app/Http/Middleware/Authenticate.php'    => '<?php namespace App\Http\Middleware; class Authenticate {}',
            'app/Providers/RouteServiceProvider.php'  => '<?php namespace App\Providers; class RouteServiceProvider {}'
        ];
        
        // Test each file
        foreach ($files as $path => $content) {
            $fullPath = $this->tempDir . '/' . $path;
            $dir = dirname($fullPath);
            
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            
            file_put_contents($fullPath, $content);
            
            // Essential files should be minified
            if (strpos($path, 'Controllers') !== false ||
                strpos($path, 'Models') !== false ||
                strpos($path, 'Services') !== false) {
                
                $result = $minifyLaravelFile->invokeArgs(null, [$fullPath, $content]);
                $this->assertNotFalse($result, "File should be minified: $path");
            }
            
            // Excluded files should return false
            if (strpos($path, 'Middleware/Authenticate') !== false ||
                strpos($path, 'RouteServiceProvider') !== false) {
                
                $result = $minifyLaravelFile->invokeArgs(null, [$fullPath, $content]);
                $this->assertFalse($result, "File should be excluded: $path");
            }
        }
        
        // Test content removal patterns
        $modelContent = '<?php namespace App\Models; class TestModel {
            protected $fillable=["name", "email"];
            protected $casts=["created_at" => "datetime"];
            public function __construct() {}
        }';
        
        $minifiedContent = $minifyLaravelFile->invokeArgs(null, [
            $this->tempDir . '/app/Models/TestModel.php',
            $modelContent
        ]);
        
        // Check that fillable array and empty constructor were removed
        $this->assertStringNotContainsString('$fillable', $minifiedContent);
        $this->assertStringNotContainsString('$casts', $minifiedContent);
        $this->assertStringNotContainsString('__construct', $minifiedContent);
        
        // Restore original patterns
        $laravelPatternsProperty->setValue($originalPatterns);
    }
}