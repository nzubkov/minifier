<?php

require_once 'bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * Test for minifying the specific example Pest file
 */
class FileMinifierPestExampleTest extends TestCase
{
    use MinifierTestCase;
    
    /**
     * Test minification of the provided example Pest file
     */
    public function testMinifyExamplePestFile()
    {
        // Get the minifyPHP method
        $minifyPHP = $this->getMethod('minifyPHP');
        
        // The example content from CompaniesTest.php
        $exampleContent = <<<'PHP'
<?php
/**
 * User: Nikolay Zubkov <zubkov.rabota@gmail.com>
 * Date: 09.10.2023
 * Time: 13:25
 */

use App\Actions\Company\RegisterCompany;
use App\Enums\GlobalRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertTrue;

test('create company with default image', function () {
    $data = [
        'name'    => 'Test Company',
        'inn'     => 12345678,
        'phone'   => '1234567890',
        'email'   => 'test@example.com',
    ];

    post('/api/companies', $data)->assertCreated();
    $createdCompany = Company::where('name', 'Test Company')->first();
    assertNotNull($createdCompany, 'Company was not created');
    (new RegisterCompany())($createdCompany);
    $user = User::whereEmail($data['email'])->first()->load('companies');
    assertTrue($user->companies()->where('companies.id', $createdCompany?->id)->exists());
    $user->refresh();
    assertTrue($user->role_id === GlobalRole::ADMIN()->value);
    assertSame(asset('storage/' . $createdCompany->image), asset('/storage/companies/default.png'));
});
PHP;
        
        // First check if it's detected as a Pest file
        $isPestFile = $this->getMethod('isPestFile');
        $this->assertTrue($isPestFile->invokeArgs(null, [$exampleContent]));
        
        // Minify the file
        $minified = $minifyPHP->invokeArgs(null, [$exampleContent]);
        
        // Verify comments are removed
        $this->assertStringNotContainsString('/**', $minified);
        $this->assertStringNotContainsString('* User:', $minified);
        
        // Verify that structure is preserved
        $this->assertStringContainsString("test('create company with default image',function()", $minified);
        
        // Check for key operations in the test
        $this->assertStringContainsString("post('/api/companies'", $minified);
        $this->assertStringContainsString("assertNotNull(", $minified);
        $this->assertStringContainsString("assertTrue(", $minified);
        $this->assertStringContainsString("(new RegisterCompany())", $minified);
        
        // Check that all assertions are present
        $this->assertStringContainsString("assertCreated()", $minified);
        $this->assertStringContainsString("assertNotNull(", $minified);
        $this->assertStringContainsString("assertTrue(", $minified);
        $this->assertStringContainsString("assertSame(", $minified);
        
        // Ensure we maintain proper formatting for data arrays
        $this->assertMatchesRegularExpression('/\$data=\[\s*\'name\'/', $minified);
        
        // Basic structure checks
        $this->assertStringEndsWith("});", trim($minified));
    }
}