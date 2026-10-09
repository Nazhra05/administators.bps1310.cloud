<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\LayananController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class UploadSecurityTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/bps_upload_test_' . bin2hex(random_bytes(4));
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            if ($files !== false) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }
            @rmdir($this->tempDir);
        }
    }

    /**
     * Invoke private handleLogoUpload method on LayananController via reflection.
     * @param array<string, mixed> $file
     * @return array{filename: string, error: ?string}
     */
    private function invokeUpload(array $file): array
    {
        $controller = new LayananController();
        $ref = new ReflectionClass($controller);
        $method = $ref->getMethod('handleLogoUpload');
        $method->setAccessible(true);
        /** @var array{filename: string, error: ?string} $res */
        $res = $method->invoke($controller, $file);
        return $res;
    }

    public function testRejectsShellPhp(): void
    {
        $phpFile = $this->tempDir . '/shell.php';
        file_put_contents($phpFile, "<?php phpinfo(); ?>");

        $result = $this->invokeUpload([
            'name'     => 'shell.php',
            'type'     => 'application/x-php',
            'tmp_name' => $phpFile,
            'size'     => filesize($phpFile),
            'error'    => UPLOAD_ERR_OK,
        ]);

        $this->assertNotEmpty($result['error']);
        $this->assertEmpty($result['filename']);
        $this->assertStringContainsString('tidak didukung', $result['error']);
    }

    public function testRejectsGifWithPhpPayload(): void
    {
        $gifFile = $this->tempDir . '/exploit.gif';
        // GIF header followed by PHP script
        file_put_contents($gifFile, "GIF89a; <?php echo 'hacked'; ?>");

        $result = $this->invokeUpload([
            'name'     => 'exploit.gif',
            'type'     => 'image/gif',
            'tmp_name' => $gifFile,
            'size'     => filesize($gifFile),
            'error'    => UPLOAD_ERR_OK,
        ]);

        $this->assertNotEmpty($result['error']);
        $this->assertEmpty($result['filename']);
        $this->assertStringContainsString('tidak didukung', $result['error']);
    }

    public function testRejectsDisguisedPhpJpg(): void
    {
        $fakeJpg = $this->tempDir . '/gambar.php.jpg';
        file_put_contents($fakeJpg, "<?php system(\$_GET['cmd']); ?>");

        $result = $this->invokeUpload([
            'name'     => 'gambar.php.jpg',
            'type'     => 'image/jpeg',
            'tmp_name' => $fakeJpg,
            'size'     => filesize($fakeJpg),
            'error'    => UPLOAD_ERR_OK,
        ]);

        $this->assertNotEmpty($result['error']);
        $this->assertEmpty($result['filename']);
    }

    public function testRejectsOversizedUpload(): void
    {
        $bigFile = $this->tempDir . '/large.jpg';
        file_put_contents($bigFile, str_repeat('A', 3 * 1024 * 1024)); // 3MB

        $result = $this->invokeUpload([
            'name'     => 'large.jpg',
            'type'     => 'image/jpeg',
            'tmp_name' => $bigFile,
            'size'     => 3 * 1024 * 1024,
            'error'    => UPLOAD_ERR_OK,
        ]);

        $this->assertNotEmpty($result['error']);
        $this->assertEmpty($result['filename']);
        $this->assertStringContainsString('2MB', $result['error']);
    }

    public function testAcceptsAndReEncodesValidJpg(): void
    {
        $validJpg = $this->tempDir . '/valid.jpg';
        $gdImg = imagecreatetruecolor(50, 50);
        $bgColor = imagecolorallocate($gdImg, 0, 102, 179);
        if ($bgColor !== false) {
            imagefill($gdImg, 0, 0, $bgColor);
        }
        imagejpeg($gdImg, $validJpg);
        imagedestroy($gdImg);

        $result = $this->invokeUpload([
            'name'     => 'valid.jpg',
            'type'     => 'image/jpeg',
            'tmp_name' => $validJpg,
            'size'     => filesize($validJpg),
            'error'    => UPLOAD_ERR_OK,
        ]);

        $this->assertNull($result['error']);
        $this->assertNotEmpty($result['filename']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}\.jpg$/', $result['filename']);

        // Clean up uploaded file
        $uploadedPath = dirname(__DIR__, 2) . '/public/uploads/' . $result['filename'];
        if (file_exists($uploadedPath)) {
            @unlink($uploadedPath);
        }
    }
}
