<?php

require_once 'bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the FileMinifier class
 * Testing full directory processing and combined output scenarios
 */
class FileMinifierIntegrationTest extends TestCase
{
    use MinifierTestCase;
    
    /**
     * Temporary directory for test files
     */
    private $tempDir;
    
    /**
     * Output directory for minified files
     */
    private $outputDir;
    
    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $totalFilesProperty = $this->getProperty('totalFiles');
        $processedFilesProperty = $this->getProperty('processedFiles');
        $totalFilesProperty->setValue(null, 0);
        $processedFilesProperty->setValue(null, 0);
        
        // Create temporary directories for testing
        $this->tempDir = sys_get_temp_dir() . '/file-minifier-integration-' . uniqid();
        $this->outputDir = $this->tempDir . '-output';
        
        mkdir($this->tempDir, 0777, true);
        mkdir($this->outputDir, 0777, true);
        
        // Create test directory structure with sample files
        $this->createTestFiles();
    }
    
    private function getProperty($name)
    {
        $class = new ReflectionClass('FileMinifier');
        $property = $class->getProperty($name);
        $property->setAccessible(true);
        return $property;
    }
    
    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        // Remove temporary directories and all contents
        $this->recursiveRemoveDir($this->tempDir);
        $this->recursiveRemoveDir($this->outputDir);
        
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
     * Create reflection method to access private methods
     */
    private function getMethod($name)
    {
        $class = new ReflectionClass('FileMinifier');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }
    
    /**
     * Create test files for the integration tests
     */
    private function createTestFiles()
    {
        // JavaScript files
        $jsFiles = [
            'js/app.js' => '
            // Main application file
            const app = {
                init: function() {
                    console.log("Application initialized");
                    this.setupEvents();
                },
                
                setupEvents: function() {
                    // Setup event handlers
                    document.querySelector(".btn").addEventListener("click", this.handleClick);
                },
                
                handleClick: function(e) {
                    console.log("Button clicked", e);
                    /*
                     * Multi-line comment
                     * with extra whitespace
                     */
                    return true;
                }
            };
            
            app.init();
            ',
            
            'js/utils.js' => '
            // Utility functions
            function formatCurrency(amount) {
                return "$" + amount.toFixed(2);
            }
            
            function calculateTax(amount, rate) {
                // Calculate tax amount
                return amount * (rate / 100);
            }
            '
        ];
        
        // CSS files
        $cssFiles = [
            'css/styles.css' => '
            /* Main styles */
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                margin: 0;
                padding: 20px;
            }
            
            .container {
                max-width: 1200px;
                margin: 0 auto;
            }
            
            /* Buttons */
            .btn {
                display: inline-block;
                background-color: #4CAF50;
                color: white;
                padding: 10px 15px;
                border: none;
                cursor: pointer;
            }
            '
        ];
        
        // PHP files
        $phpFiles = [
            'src/Helper.php' => '<?php
            /**
             * Helper class with utility functions
             */
            class Helper
            {
                /**
                 * Format a date string
                 */
                public static function formatDate($date, $format = "Y-m-d")
                {
                    // Convert string to DateTime if needed
                    if (!$date instanceof DateTime) {
                        $date = new DateTime($date);
                    }
                    
                    return $date->format($format);
                }
                
                /**
                 * Sanitize a string for output
                 */
                public static function sanitize($string)
                {
                    /* Remove any potentially harmful characters */
                    return htmlspecialchars($string, ENT_QUOTES, "UTF-8");
                }
            }
            ',
            
            'src/Models/User.php' => '<?php
            namespace Models;
            
            /**
             * User model
             */
            class User
            {
                private $id;
                private $name;
                private $email;
                
                public function __construct($id, $name, $email)
                {
                    $this->id = $id;
                    $this->name = $name;
                    $this->email = $email;
                }
                
                // Getters
                public function getId()
                {
                    return $this->id;
                }
                
                public function getName()
                {
                    return $this->name;
                }
                
                public function getEmail()
                {
                    return $this->email;
                }
            }
            '
        ];
        
        // Laravel-style files
        $laravelFiles = [
            'app/Http/Controllers/UserController.php' => '<?php
            namespace App\Http\Controllers;
            
            use App\Models\User;
            
            class UserController extends Controller
            {
                /**
                 * Display a listing of users
                 */
                public function index()
                {
                    $users = User::all();
                    return view("users.index", compact("users"));
                }
                
                /**
                 * Show a specific user
                 */
                public function show($id)
                {
                    $user = User::findOrFail($id);
                    return view("users.show", compact("user"));
                }
            }
            ',
            
            'app/Models/User.php' => '<?php
            namespace App\Models;
            
            use Illuminate\Database\Eloquent\Model;
            
            class User extends Model
            {
                protected $fillable = [
                    "name",
                    "email",
                    "password"
                ];
                
                protected $casts = [
                    "email_verified_at" => "datetime",
                    "created_at" => "datetime",
                    "updated_at" => "datetime"
                ];
                
                public function posts()
                {
                    return $this->hasMany(Post::class);
                }
            }
            ',
            
            'app/Http/Middleware/Authenticate.php' => '<?php
            namespace App\Http\Middleware;
            
            use Closure;
            
            class Authenticate
            {
                public function handle($request, Closure $next)
                {
                    // Basic middleware that should be excluded in Laravel mode
                    if (!auth()->check()) {
                        return redirect("login");
                    }
                    
                    return $next($request);
                }
            }
            '
        ];
        
        // Create all the files
        $this->createFilesFromArray($jsFiles);
        $this->createFilesFromArray($cssFiles);
        $this->createFilesFromArray($phpFiles);
        $this->createFilesFromArray($laravelFiles);
    }
    
    /**
     * Create files from an array mapping paths to content
     */
    private function createFilesFromArray($files)
    {
        foreach ($files as $path => $content) {
            $fullPath = $this->tempDir . '/' . $path;
            $dir = dirname($fullPath);
            
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            
            file_put_contents($fullPath, $content);
        }
    }
    
    /**
     * Test processing an entire directory
     */
    public function testProcessDirectory()
    {
        $processDirectory = $this->getMethod('processDirectory');
        
        // Process the directory
        $result = $processDirectory->invokeArgs(null, [$this->tempDir, $this->outputDir]);
        
        // Check that processing was successful
        $this->assertTrue($result);
        
        // Check that output directory structure was created
        $this->assertDirectoryExists($this->outputDir . '/js');
        $this->assertDirectoryExists($this->outputDir . '/css');
        $this->assertDirectoryExists($this->outputDir . '/src/Models');
        
        // Check that files were minified
        $this->assertFileExists($this->outputDir . '/js/app.js');
        $this->assertFileExists($this->outputDir . '/css/styles.css');
        $this->assertFileExists($this->outputDir . '/src/Helper.php');
        $this->assertFileExists($this->outputDir . '/src/Models/User.php');
        
        // Verify content was minified
        $minifiedJs = file_get_contents($this->outputDir . '/js/app.js');
        $minifiedCss = file_get_contents($this->outputDir . '/css/styles.css');
        $minifiedPhp = file_get_contents($this->outputDir . '/src/Helper.php');
        
        // Check that comments were removed
        $this->assertStringNotContainsString('// Main application file', $minifiedJs);
        $this->assertStringNotContainsString('/* Main styles */', $minifiedCss);
        $this->assertStringNotContainsString('/**', $minifiedPhp);
        
        // Check that whitespace was minimized
        $this->assertStringNotContainsString('    ', $minifiedJs);
        $this->assertStringNotContainsString('    ', $minifiedCss);
        $this->assertStringNotContainsString('    ', $minifiedPhp);
    }
    
    /**
     * Test combining files from a directory
     */
    public function testProcessDirectoryToCombinedFile()
    {
        $processCombined = $this->getMethod('processDirectoryToCombinedFile');
        
        // Process the JS directory to a combined file
        $outputFile = $this->outputDir . '/combined.js';
        $result = $processCombined->invokeArgs(null, [$this->tempDir . '/js', $outputFile]);
        
        // Check that processing was successful
        $this->assertTrue($result);
        
        // Check that the combined file exists
        $this->assertFileExists($outputFile);
        
        // Verify combined content
        $combinedContent = file_get_contents($outputFile);
        
        // Check that both original files are included
        $this->assertStringContainsString('app.init', $combinedContent);
        $this->assertStringContainsString('formatCurrency', $combinedContent);
        $this->assertStringContainsString('calculateTax', $combinedContent);
        
        // Check that comments were removed
        $this->assertStringNotContainsString('// Main application file', $combinedContent);
        $this->assertStringNotContainsString('// Utility functions', $combinedContent);
        
        // Check for file headers in combined file
        $this->assertStringContainsString('/* File: app.js', $combinedContent);
        $this->assertStringContainsString('/* File: utils.js', $combinedContent);
    }
    
    /**
     * Test Laravel-specific directory processing
     */
    public function testProcessLaravelDirectory()
    {
        $processLaravelDir = $this->getMethod('processLaravelDirectory');
        
        // Process Laravel directory
        $result = $processLaravelDir->invokeArgs(null, [$this->tempDir, $this->outputDir]);
        
        // Check that processing was successful
        $this->assertTrue($result);
        
        // Check that essential files were processed
        $this->assertFileExists($this->outputDir . '/app/Http/Controllers/UserController.php');
        $this->assertFileExists($this->outputDir . '/app/Models/User.php');
        
        // Check that middleware was excluded
        $this->assertFileDoesNotExist($this->outputDir . '/app/Http/Middleware/Authenticate.php');
        
        // Verify user model content was optimized
        $userModel = file_get_contents($this->outputDir . '/app/Models/User.php');
        
        // Check that fillable array was removed
        $this->assertStringNotContainsString('$fillable', $userModel);
        
        // Check that casts were removed
        $this->assertStringNotContainsString('$casts', $userModel);
    }
    
    /**
     * Test combining Laravel files
     */
    public function testProcessLaravelDirectoryToCombinedFile()
    {
        $processLaravelCombined = $this->getMethod('processLaravelDirectoryToCombinedFile');
        
        // Process Laravel directory to a combined file
        $outputFile = $this->outputDir . '/combined-laravel.php';
        $result = $processLaravelCombined->invokeArgs(null, [$this->tempDir, $outputFile]);
        
        // Check that processing was successful
        $this->assertTrue($result);
        
        // Check that the combined file exists
        $this->assertFileExists($outputFile);
        
        // Verify combined content
        $combinedContent = file_get_contents($outputFile);
        
        // Check that PHP tag exists
        $this->assertStringContainsString('<?php', $combinedContent);
        
        // Check that controller exists in combined file
        $this->assertStringContainsString('UserController', $combinedContent);
        
        // Check that model exists in combined file
        $this->assertStringContainsString('class User extends Model', $combinedContent);
        
        // Check that middleware was excluded
        $this->assertStringNotContainsString('class Authenticate', $combinedContent);
        
        // Check for file headers in combined file - updated to match actual format
        $this->assertStringContainsString('/* File: /app/Models/User.php', $combinedContent);
        $this->assertStringContainsString('/* File: /app/Http/Controllers/UserController.php', $combinedContent);
    }
    
    /**
     * Test the main process method with different configurations
     */
    public function testProcess()
    {
        $process = $this->getMethod('process');
        
        // Test 1: Process a single file
        $singleFile = $this->tempDir . '/js/app.js';
        $result = $process->invokeArgs(null, [$singleFile, $this->outputDir]);
        $this->assertIsNumeric($result);
        $this->assertGreaterThan(0, $result);
        $this->assertFileExists($this->outputDir . DIRECTORY_SEPARATOR . 'app.js');
        
        // Test 2: Process a directory
        $jsDir = $this->tempDir . '/js';
        $result = $process->invokeArgs(null, [$jsDir, $this->outputDir . '/js-output']);
        $this->assertTrue($result);
        $this->assertFileExists($this->outputDir . '/js-output/app.js');
        $this->assertFileExists($this->outputDir . '/js-output/utils.js');
        
        // Test 3: Process a directory with combine option
        $cssDir = $this->tempDir . '/css';
        $result = $process->invokeArgs(null, [
            $cssDir,
            $this->outputDir . '/combined.css',
            ['combine' => true]
        ]);
        $this->assertTrue($result);
        $this->assertFileExists($this->outputDir . '/combined.css');
        
        // Test 4: Process with Laravel option
        $laravelCombinedOutput = $this->outputDir . '/laravel-combined.php';
        $result = $process->invokeArgs(null, [
            $this->tempDir,
            $laravelCombinedOutput,
            ['laravel' => true, 'combine' => true]
        ]);
        
        // The combined output should work better since it doesn't depend on directory structure
        $this->assertTrue($result, "Laravel combined processing failed");
        $this->assertFileExists($laravelCombinedOutput, "Laravel combined output file not created");
        
        // Check content of the combined file
        if (file_exists($laravelCombinedOutput)) {
            $content = file_get_contents($laravelCombinedOutput);
            $this->assertStringContainsString('<?php', $content);
            $this->assertStringContainsString('class UserController', $content, "UserController not found in combined output");
        }
    }
    
    /**
     * Helper method to find a file by name in a directory and its subdirectories
     */
    private function findFileInDirectory($directory, $filename)
    {
        if (!is_dir($directory)) {
            return false;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && basename($file) === $filename) {
                return true;
            }
        }
        
        return false;
    }
}