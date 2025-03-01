<?php

require_once 'bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * TestCase focused on Laravel-specific features of the FileMinifier
 */
class FileMinifierLaravelTest extends TestCase
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
        
        $totalFilesProperty = $this->getProperty('totalFiles');
        $processedFilesProperty = $this->getProperty('processedFiles');
        $totalFilesProperty->setValue(null, 0);
        $processedFilesProperty->setValue(null, 0);
        
        // Create temporary directory for Laravel test files
        $this->tempDir = sys_get_temp_dir() . '/file-minifier-laravel-tests-' . uniqid();
        mkdir($this->tempDir, 0777, true);
        
        // Create basic Laravel application structure
        $this->createLaravelStructure();
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
     * Create a reflection method to access private methods
     */
    private function getMethod($name)
    {
        $class = new ReflectionClass('FileMinifier');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }
    
    /**
     * Create a reflection property to access private properties
     */
    private function getProperty($name)
    {
        $class = new ReflectionClass('FileMinifier');
        $property = $class->getProperty($name);
        $property->setAccessible(true);
        return $property;
    }
    
    /**
     * Create Laravel application structure for testing
     */
    private function createLaravelStructure()
    {
        $dirs = [
            'app/Http/Controllers',
            'app/Http/Controllers/API',
            'app/Http/Middleware',
            'app/Http/Requests',
            'app/Http/Resources',
            'app/Models',
            'app/Services',
            'app/Events',
            'app/Listeners',
            'app/Providers',
            'app/Notifications',
            'app/Jobs',
            'app/Mail',
            'app/Rules',
            'app/Observers',
            'app/Policies',
            'app/Actions',
            'app/Dto',
            'app/Enums',
            'app/Imports',
            'app/TelegramBot'
        ];
        
        // Create directory structure
        foreach ($dirs as $dir) {
            mkdir($this->tempDir . '/' . $dir, 0777, true);
        }
        
        // Create sample Laravel files
        $this->createLaravelFiles();
    }
    
    /**
     * Create sample Laravel files for testing
     */
    private function createLaravelFiles()
    {
        $laravelFiles = [
            // Essential files (should be processed)
            'app/Http/Controllers/UserController.php' =>
                '<?php namespace App\Http\Controllers;
                class UserController {
                    public function index() { return "User list"; }
                }',
            
            'app/Http/Controllers/API/ProductController.php' =>
                '<?php namespace App\Http\Controllers\API;
                class ProductController {
                    public function all() { return ["products" => []]; }
                }',
            
            'app/Models/User.php' =>
                '<?php namespace App\Models;
                class User {
                    protected $fillable=["name", "email"];
                    protected $casts=["created_at" => "datetime"];
                    
                    public function __construct() {
                        // Empty constructor
                    }
                    
                    public function toArray() {
                        return [
                            "id" => $this->id,
                            "name" => $this->name
                        ];
                    }
                }',
            
            'app/Services/EmailService.php' =>
                '<?php namespace App\Services;
                class EmailService {
                    public function send($to, $subject, $body) {
                        // Implementation
                    }
                }',
            
            'app/Events/UserRegisteredEvent.php' =>
                '<?php namespace App\Events;
                class UserRegisteredEvent {
                    private $user;
                    public function __construct($user) {
                        $this->user = $user;
                    }
                }',
            
            'app/Listeners/SendWelcomeEmailListener.php' =>
                '<?php namespace App\Listeners;
                class SendWelcomeEmailListener {
                    public function handle($event) {
                        // Implementation
                    }
                }',
            
            'app/Notifications/PasswordResetNotification.php' =>
                '<?php namespace App\Notifications;
                class PasswordResetNotification {
                    private $token;
                    public function __construct($token) {
                        $this->token = $token;
                    }
                }',
            
            'app/TelegramBot/Commands/StartCommand.php' =>
                '<?php namespace App\TelegramBot\Commands;
                class StartCommand {
                    public function handle() {
                        // Implementation
                    }
                }',
            
            'app/Jobs/ProcessPodcast.php' =>
                '<?php namespace App\Jobs;
                class ProcessPodcast {
                    public function handle() {
                        // Process podcast
                    }
                }',
            
            'app/Http/Requests/StoreUserRequest.php' =>
                '<?php namespace App\Http\Requests;
                class StoreUserRequest {
                    public function rules() {
                        return [
                            "name" => "required",
                            "email" => "required|email"
                        ];
                    }
                }',
            
            'app/Http/Resources/UserResource.php' =>
                '<?php namespace App\Http\Resources;
                class UserResource {
                    public function toArray($request) {
                        return [
                            "id" => $this->id,
                            "name" => $this->name,
                            "email" => $this->email
                        ];
                    }
                }',
            
            'app/Dto/UserDto.php' =>
                '<?php namespace App\Dto;
                class UserDto {
                    public $name;
                    public $email;
                    
                    public function __construct($name, $email) {
                        $this->name = $name;
                        $this->email = $email;
                    }
                }',
            
            'app/Actions/CreateUserAction.php' =>
                '<?php namespace App\Actions;
                class CreateUserAction {
                    public function execute($data) {
                        // Create user
                    }
                }',
            
            'app/Enums/UserStatus.php' =>
                '<?php namespace App\Enums;
                class UserStatus {
                    const ACTIVE = "active";
                    const INACTIVE = "inactive";
                    const PENDING = "pending";
                }',
            
            'app/Mail/WelcomeMail.php' =>
                '<?php namespace App\Mail;
                class WelcomeMail {
                    public function build() {
                        // Build email
                    }
                }',
            
            'app/Rules/PasswordStrength.php' =>
                '<?php namespace App\Rules;
                class PasswordStrength {
                    public function passes($attribute, $value) {
                        // Validate password strength
                        return true;
                    }
                }',
            
            'app/Imports/UsersImport.php'          =>
                '<?php namespace App\Imports;
                class UsersImport {
                    public function model(array $row) {
                        // Convert row to model
                    }
                }',
            
            // Excluded files (should NOT be processed)
            'app/Http/Middleware/Authenticate.php' =>
                '<?php namespace App\Http\Middleware;
                class Authenticate {
                    public function handle($request, $next) {
                        // Authentication logic
                        return $next($request);
                    }
                }',
            
            'app/Http/Middleware/EncryptCookies.php' =>
                '<?php namespace App\Http\Middleware;
                class EncryptCookies {
                    protected $except = [];
                }',
            
            'app/Http/Middleware/TrustProxies.php' =>
                '<?php namespace App\Http\Middleware;
                class TrustProxies {
                    protected $proxies;
                }',
            
            'app/Providers/RouteServiceProvider.php' =>
                '<?php namespace App\Providers;
                class RouteServiceProvider {
                    public function boot() {
                        // Boot logic
                    }
                }',
            
            'app/Providers/EventServiceProvider.php' =>
                '<?php namespace App\Providers;
                class EventServiceProvider {
                    protected $listen = [
                        // Event listeners
                    ];
                }',
        ];
        
        // Create all the Laravel files
        foreach ($laravelFiles as $path => $content) {
            $fullPath = $this->tempDir . '/' . $path;
            file_put_contents($fullPath, $content);
        }
    }
    
    /**
     * Test Laravel file detection - essential files
     */
    public function testLaravelFileDetectionEssential()
    {
        $minifyLaravelFile = $this->getMethod('minifyLaravelFile');
        
        // Test essential files (should be processed)
        $essentialFiles = [
            'app/Http/Controllers/UserController.php',
            'app/Http/Controllers/API/ProductController.php',
            'app/Models/User.php',
            'app/Services/EmailService.php',
            'app/Events/UserRegisteredEvent.php',
            'app/Listeners/SendWelcomeEmailListener.php',
            'app/Notifications/PasswordResetNotification.php',
            'app/Jobs/ProcessPodcast.php',
            'app/Http/Requests/StoreUserRequest.php',
            'app/Http/Resources/UserResource.php',
            'app/Dto/UserDto.php',
            'app/Actions/CreateUserAction.php',
            'app/Enums/UserStatus.php',
            'app/Mail/WelcomeMail.php',
            'app/Rules/PasswordStrength.php',
            'app/Imports/UsersImport.php'
        ];
        
        foreach ($essentialFiles as $path) {
            $fullPath = $this->tempDir . '/' . $path;
            $content = file_get_contents($fullPath);
            
            $result = $minifyLaravelFile->invokeArgs(null, [$fullPath, $content]);
            
            // Should return minified content, not false
            $this->assertNotFalse($result, "Essential file should be processed: $path");
            $this->assertIsString($result, "Result should be a string for essential file: $path");
        }
    }
    
    /**
     * Test Laravel file detection - excluded files
     */
    public function testLaravelFileDetectionExcluded()
    {
        $minifyLaravelFile = $this->getMethod('minifyLaravelFile');
        
        // Test excluded files (should NOT be processed)
        $excludedFiles = [
            'app/Http/Middleware/Authenticate.php',
            'app/Http/Middleware/EncryptCookies.php',
            'app/Http/Middleware/TrustProxies.php',
            'app/Providers/RouteServiceProvider.php',
            'app/Providers/EventServiceProvider.php'
        ];
        
        foreach ($excludedFiles as $path) {
            $fullPath = $this->tempDir . '/' . $path;
            $content = file_get_contents($fullPath);
            
            $result = $minifyLaravelFile->invokeArgs(null, [$fullPath, $content]);
            
            // Should return false for excluded files
            $this->assertFalse($result, "Excluded file should NOT be processed: $path");
        }
    }
    
    /**
     * Test Laravel content removal patterns
     */
    public function testLaravelContentRemoval()
    {
        $minifyLaravelFile = $this->getMethod('minifyLaravelFile');
        
        // Check User model for content removal
        $userPath = $this->tempDir . '/app/Models/User.php';
        $content = file_get_contents($userPath);
        
        $result = $minifyLaravelFile->invokeArgs(null, [$userPath, $content]);
        
        // Check that patterns were removed
        $this->assertStringNotContainsString('$fillable', $result, 'Fillable array should be removed');
        $this->assertStringNotContainsString('$casts', $result, 'Casts array should be removed');
        $this->assertStringNotContainsString('__construct', $result, 'Empty constructor should be removed');
        $this->assertStringNotContainsString('toArray', $result, 'Simple toArray method should be removed');
    }
    
    /**
     * Test processing a Laravel directory
     */
    public function testProcessLaravelDirectory()
    {
        $processLaravelDirectory = $this->getMethod('processLaravelDirectory');
        
        // Process the Laravel directory
        $outputDir = $this->tempDir . '-output';
        $result = $processLaravelDirectory->invokeArgs(null, [$this->tempDir, $outputDir]);
        
        // Check that processing was successful
        $this->assertTrue($result);
        
        // Check that essential files were processed
        $this->assertFileExists($outputDir . '/app/Http/Controllers/UserController.php');
        $this->assertFileExists($outputDir . '/app/Http/Controllers/API/ProductController.php');
        $this->assertFileExists($outputDir . '/app/Models/User.php');
        $this->assertFileExists($outputDir . '/app/Services/EmailService.php');
        $this->assertFileExists($outputDir . '/app/Events/UserRegisteredEvent.php');
        $this->assertFileExists($outputDir . '/app/Listeners/SendWelcomeEmailListener.php');
        
        // Check that excluded files were not processed
        $this->assertFileDoesNotExist($outputDir . '/app/Http/Middleware/Authenticate.php');
        $this->assertFileDoesNotExist($outputDir . '/app/Http/Middleware/EncryptCookies.php');
        $this->assertFileDoesNotExist($outputDir . '/app/Providers/RouteServiceProvider.php');
        
        // Verify content was minified
        $userModel = file_get_contents($outputDir . '/app/Models/User.php');
        
        // Check minification and pattern removal
        $this->assertStringNotContainsString('    ', $userModel, 'Whitespace should be removed');
        $this->assertStringNotContainsString('$fillable', $userModel, 'Fillable array should be removed');
        $this->assertStringNotContainsString('$casts', $userModel, 'Casts array should be removed');
        $this->assertStringNotContainsString('__construct', $userModel, 'Empty constructor should be removed');
    }
    
    /**
     * Test Laravel file combined output
     */
    public function testProcessLaravelDirectoryToCombinedFile()
    {
        $processLaravelCombined = $this->getMethod('processLaravelDirectoryToCombinedFile');
        
        // Process Laravel directory to a combined file
        $outputFile = $this->tempDir . '-combined.php';
        $result = $processLaravelCombined->invokeArgs(null, [$this->tempDir, $outputFile]);
        
        // Check that processing was successful
        $this->assertTrue($result);
        
        // Check that the combined file exists
        $this->assertFileExists($outputFile);
        
        // Get the combined file content
        $combinedContent = file_get_contents($outputFile);
        
        // Check that PHP tag exists
        $this->assertStringContainsString('<?php', $combinedContent);
        
        // Check for file grouping comments
        $this->assertStringContainsString('// models', $combinedContent);
        $this->assertStringContainsString('// controllers', $combinedContent);
        $this->assertStringContainsString('// services', $combinedContent);
        
        // Check for essential files in combined content
        $this->assertStringContainsString('class UserController', $combinedContent);
        $this->assertStringContainsString('class ProductController', $combinedContent);
        $this->assertStringContainsString('class User', $combinedContent);
        $this->assertStringContainsString('class EmailService', $combinedContent);
        
        // Check that excluded files are not in combined content
        $this->assertStringNotContainsString('class Authenticate', $combinedContent);
        $this->assertStringNotContainsString('class RouteServiceProvider', $combinedContent);
        
        // Verify content removal patterns were applied
        $this->assertStringNotContainsString('$fillable', $combinedContent);
        $this->assertStringNotContainsString('$casts', $combinedContent);
        $this->assertStringNotContainsString('__construct()', $combinedContent);
    }
    
    /**
     * Test file ordering in Laravel combined output
     */
    public function testLaravelCombinedFileOrdering()
    {
        $processLaravelCombined = $this->getMethod('processLaravelDirectoryToCombinedFile');
        
        // Process Laravel directory to a combined file
        $outputFile = $this->tempDir . '-ordering.php';
        $result = $processLaravelCombined->invokeArgs(null, [$this->tempDir, $outputFile]);
        
        // Check that processing was successful
        $this->assertTrue($result);
        
        // Get the combined file content
        $combinedContent = file_get_contents($outputFile);
        
        // Check proper file ordering
        $modelsPos = strpos($combinedContent, '// models');
        $servicesPos = strpos($combinedContent, '// services');
        $eventsPos = strpos($combinedContent, '// events');
        $listenersPos = strpos($combinedContent, '// listeners');
        $controllersPos = strpos($combinedContent, '// controllers');
        
        // Verify ordering exists and is correct
        $this->assertNotFalse($modelsPos, 'Models section should exist');
        $this->assertNotFalse($servicesPos, 'Services section should exist');
        $this->assertNotFalse($controllersPos, 'Controllers section should exist');
        
        // Check sequence (if all sections exist)
        if ($modelsPos !== false && $servicesPos !== false && $controllersPos !== false) {
            $this->assertLessThan($controllersPos, $modelsPos, 'Models should come before controllers');
        }
    }
}