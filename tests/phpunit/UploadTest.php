<?php


namespace Rundiz\Upload\Tests;

class UploadTest extends \PHPUnit\Framework\TestCase
{


    public function __destruct()
    {
        $files = glob($this->temp_folder.'*.*');
        if (is_array($files)) {
            foreach ($files as $file) {
                if (is_file($file) && is_writable($file) && strpos($file, '.gitkeep') === false) {
                    unlink($file);
                }
            }
            unset($file);
        }
        unset($files);
    }// tearDownAfterClass


    private $asset_folder;
    private $temp_folder;
    private $file_text;
    private $file_dangertext;
    private $file_falseimage;
    private $file_51kbimage;
    private $files_multiple;


    public function setUp()
    {
        $this->asset_folder = __DIR__.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR;
        $this->temp_folder = __DIR__.DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR;

        // copy files from assets folder to temp to prevent file deletion while set it to $_FILES.
        $files = glob($this->asset_folder.'*.*');
        if (is_array($files)) {
            foreach ($files as $file) {
                $destination = str_replace($this->asset_folder, $this->temp_folder, $file);
                copy($file, $destination);
                unset($destination);
            }
            unset($file);
        }
        unset($files);

        // setup file same as it is in $_FILES.
        $this->file_text['filename'] = array(
            'name' => 'text.txt',
            'type' => 'text/plain',
            'tmp_name' => $this->temp_folder.'text.txt',
            'error' => 0,
            'size' => filesize($this->temp_folder.'text.txt'),
        );
        $this->file_dangertext['filename'] = array(
            'name' => 'not-safe-text.txt',
            'type' => 'text/plain',
            'tmp_name' => $this->temp_folder.'not-safe-text.txt',
            'error' => 0,
            'size' => filesize($this->temp_folder.'not-safe-text.txt'),
        );
        $this->file_falseimage['filename'] = array(
            'name' => 'false-image.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => $this->temp_folder.'false-image.jpg',
            'error' => 0,
            'size' => filesize($this->temp_folder.'false-image.jpg'),
        );
        $this->file_51kbimage['filename'] = array(
            'name' => '51KB-image.JPG',
            'type' => 'image/jpeg',
            'tmp_name' => $this->temp_folder.'51KB-image.JPG',
            'error' => 0,
            'size' => filesize($this->temp_folder.'51KB-image.JPG'),
        );
        $this->files_multiple['filename'] = array(
            'name' => array(
                0 => 'text.txt',
                1 => 'not-safe-text.txt',
                2 => 'false-image.jpg',
                3 => '51KB-image.JPG',
            ),
            'type' => array(
                0 => 'text/plain',
                1 => 'text/plain',
                2 => 'image/jpeg',
                3 => 'image/jpeg',
            ),
            'tmp_name' => array(
                0 => $this->temp_folder.'text.txt',
                1 => $this->temp_folder.'not-safe-text.txt',
                2 => $this->temp_folder.'false-image.jpg',
                3 => $this->temp_folder.'51KB-image.JPG',
            ),
            'error' => array(
                0 => 0,
                1 => 0,
                2 => 0,
                3 => 0,
            ),
            'size' => array(
                0 => filesize($this->temp_folder.'text.txt'),
                1 => filesize($this->temp_folder.'not-safe-text.txt'),
                2 => filesize($this->temp_folder.'false-image.jpg'),
                3 => filesize($this->temp_folder.'51KB-image.JPG'),
            ),
        );
    }// setUp


    public function tearDown()
    {
        $this->file_51kbimage = null;
        $this->file_dangertext = null;
        $this->file_falseimage = null;
        $this->file_text = null;
        $this->files_multiple = null;
        $_FILES = array();
    }// tearDown


    public function testGetUploadMimeType()
    {
        $Upload = new \Rundiz\Upload\Upload('filename');

        $_FILES = $this->file_text;
        $this->assertTrue(false !== strpos($Upload->testGetUploadedMimetype('filename'), 'text/plain'));
        $_FILES = $this->file_dangertext;
        $this->assertTrue(false !== strpos($Upload->testGetUploadedMimetype('filename'), 'text/plain'));
        $_FILES = $this->file_falseimage;
        $this->assertTrue(false !== strpos($Upload->testGetUploadedMimetype('filename'), 'text/plain'));
        $_FILES = $this->file_51kbimage;
        $this->assertTrue(false !== strpos($Upload->testGetUploadedMimetype('filename'), 'image/jpeg'));

        unset($Upload);
    }// testGetUploadMimeType


    public function testAllowedExtensionAndMimeType()
    {
        $Upload = new \Rundiz\Upload\Tests\ExtendedUploadForTest('filename');
        $default_mime_types_file = 'file-extensions-mime-types.php';
        if (is_file(dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'Rundiz'.DIRECTORY_SEPARATOR.'Upload'.DIRECTORY_SEPARATOR.$default_mime_types_file)) {
            $Upload->file_extensions_mime_types = include dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'Rundiz'.DIRECTORY_SEPARATOR.'Upload'.DIRECTORY_SEPARATOR.$default_mime_types_file;
        }
        unset($default_mime_types_file);

        $_FILES = $this->file_text;
        $Upload->setFilesPropertyForCheck();
        $Upload->allowed_file_extensions = array('txt');
        $this->assertTrue($Upload->validateExtensionAndMimeType());
        $_FILES = $this->file_dangertext;
        $Upload->setFilesPropertyForCheck();
        $Upload->allowed_file_extensions = array('txt');
        $this->assertTrue($Upload->validateExtensionAndMimeType());
        $_FILES = $this->file_falseimage;
        $Upload->setFilesPropertyForCheck();
        $Upload->allowed_file_extensions = array('jpg');
        $this->assertFalse($Upload->validateExtensionAndMimeType());
        $_FILES = $this->file_51kbimage;
        $Upload->setFilesPropertyForCheck();
        $Upload->allowed_file_extensions = array('jpg');
        $this->assertTrue($Upload->validateExtensionAndMimeType());

        unset($Upload);
    }// testAllowedExtensionAndMimeType


    public function testMaxFileSize()
    {
        $Upload = new \Rundiz\Upload\Tests\ExtendedUploadForTest('filename');

        $_FILES = $this->file_text;
        $Upload->setFilesPropertyForCheck();
        $Upload->max_file_size = 1500;
        $this->assertTrue($Upload->validateFileSize());
        $_FILES = $this->file_dangertext;
        $Upload->setFilesPropertyForCheck();
        $Upload->max_file_size = 1500;
        $this->assertTrue($Upload->validateFileSize());
        $_FILES = $this->file_falseimage;
        $Upload->setFilesPropertyForCheck();
        $Upload->max_file_size = 1500;
        $this->assertTrue($Upload->validateFileSize());
        $_FILES = $this->file_51kbimage;
        $Upload->setFilesPropertyForCheck();
        $Upload->max_file_size = 50000;
        $this->assertFalse($Upload->validateFileSize());

        unset($Upload);
    }// testMaxFileSize


    public function testSecurityScan()
    {
        $Upload = new \Rundiz\Upload\Tests\ExtendedUploadForTest('filename');

        $_FILES = $this->file_text;
        $Upload->setFilesPropertyForCheck();
        $this->assertTrue($Upload->securityScan());
        $_FILES = $this->file_dangertext;
        $Upload->setFilesPropertyForCheck();
        $this->assertFalse($Upload->securityScan());
        $_FILES = $this->file_falseimage;
        $Upload->setFilesPropertyForCheck();
        $this->assertTrue($Upload->securityScan());
        $_FILES = $this->file_51kbimage;
        $Upload->setFilesPropertyForCheck();
        $this->assertTrue($Upload->securityScan());

        unset($Upload);
    }// testSecurityScan


    public function testNewFileName()
    {
        $Upload = new \Rundiz\Upload\Tests\ExtendedUploadForTest('filename');
        $the_new_file_name = 'TEST -= !@#$%^&*()_+ []\\ {}| ;\' :" ,./ <>? `~';
        $expect_new_file_name = 'TEST -= #$^&()_+ [] {} ;\'  ,.  `~';

        $_FILES = $this->file_text;
        $Upload->setFilesPropertyForCheck();
        $Upload->new_file_name = $the_new_file_name;
        $Upload->setNewFileName();
        $this->assertEquals($expect_new_file_name, $Upload->new_file_name);

        unset($Upload);
    }// testSecurityScan


    public function testWebSafeFileName()
    {
        $Upload = new \Rundiz\Upload\Tests\ExtendedUploadForTest('filename');
        $the_new_file_name = 'TEST -= !@#$%^&*()_+ []\\ {}| ;\' :" ,./ <>? `~';
        $expect_new_file_name = 'TEST-_-';

        $_FILES = $this->file_text;
        $Upload->setFilesPropertyForCheck();
        $Upload->new_file_name = $the_new_file_name;
        $Upload->setNewFileName();
        $Upload->setWebSafeFileName();
        $this->assertEquals($expect_new_file_name, $Upload->new_file_name);

        unset($expect_new_file_name, $the_new_file_name, $Upload);
    }// testWebSafeFileName


    public function testUploadMultiple()
    {
        $_FILES = $this->files_multiple;

        $Upload = new \Rundiz\Upload\Tests\ExtendedUploadForTest('filename');
        $Upload->allowed_file_extensions = array('jpg', 'txt');
        $Upload->max_file_size = 60000;
        $default_mime_types_file = 'file-extensions-mime-types.php';
        if (is_file(dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'Rundiz'.DIRECTORY_SEPARATOR.'Upload'.DIRECTORY_SEPARATOR.$default_mime_types_file)) {
            $Upload->file_extensions_mime_types = include dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'Rundiz'.DIRECTORY_SEPARATOR.'Upload'.DIRECTORY_SEPARATOR.$default_mime_types_file;
        }
        unset($default_mime_types_file);
        $Upload->move_uploaded_to = $this->temp_folder;
        $Upload->overwrite = false;
        $Upload->security_scan = true;
        $Upload->stop_on_failed_upload_multiple = false;
        $Upload->web_safe_file_name = true;
        $upload_result = $Upload->upload();

        $this->assertTrue($upload_result);
        $this->assertGreaterThanOrEqual(2, count($Upload->error_messages));

        $Upload->clear();

        $Upload->setInputFileName('filename');
        $Upload->allowed_file_extensions = array('jpg', 'txt');
        $Upload->file_extensions_mime_types = array();
        $Upload->max_file_size = 60000;
        $Upload->move_uploaded_to = $this->temp_folder;
        $Upload->overwrite = false;
        $Upload->security_scan = false;
        $Upload->stop_on_failed_upload_multiple = false;
        $Upload->web_safe_file_name = true;
        $upload_result = $Upload->upload();

        $this->assertTrue($upload_result);
        $this->assertEquals(0, count($Upload->error_messages));

        unset($Upload, $upload_result);
    }// testUploadMultiple


    public function testUploadSingle()
    {
        $_FILES = $this->file_51kbimage;

        $Upload = new \Rundiz\Upload\Tests\ExtendedUploadForTest('filename');
        $Upload->setInputFileName('filename');
        $Upload->allowed_file_extensions = array('jpg');
        $Upload->max_file_size = 60000;
        $default_mime_types_file = 'file-extensions-mime-types.php';
        if (is_file(dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'Rundiz'.DIRECTORY_SEPARATOR.'Upload'.DIRECTORY_SEPARATOR.$default_mime_types_file)) {
            $Upload->file_extensions_mime_types = include dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'Rundiz'.DIRECTORY_SEPARATOR.'Upload'.DIRECTORY_SEPARATOR.$default_mime_types_file;
        }
        unset($default_mime_types_file);
        $Upload->move_uploaded_to = $this->temp_folder;
        $Upload->overwrite = false;
        $Upload->security_scan = true;
        $Upload->web_safe_file_name = true;
        $upload_result = $Upload->upload();

        $this->assertTrue($upload_result);
        $this->assertEquals(0, count($Upload->error_messages));

        unset($Upload, $upload_result);
    }// testUploadSingle


}
