<?php

use PHPUnit\Framework\TestCase;
use App\Classes\Database;

require_once __DIR__ . '/../backend/includes/bootstrap.php';
require_once __DIR__ . '/../backend/includes/profile_upload_helper.php';

class ProfileTest extends TestCase
{
    private $db;
    private $userId;
    private $uploadedFilePath;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();

        // Create a temporary test user
        $email = 'test+' . time() . '@example.test';
        $username = 'test_user_' . time();
        $name = 'PHPUnit Test User';
        $passwordHash = password_hash('password123', PASSWORD_DEFAULT);

        $this->userId = $this->db->insert('users', [
            'name' => $name,
            'email' => $email,
            'username' => $username,
            'password' => $passwordHash
        ]);

        if (!$this->userId) {
            $this->fail('Failed to create test user');
        }
    }

    protected function tearDown(): void
    {
        // Delete uploaded file if created
        if (!empty($this->uploadedFilePath) && file_exists($this->uploadedFilePath)) {
            @unlink($this->uploadedFilePath);
        }

        // Remove test user profile
        if ($this->userId) {
            $this->db->delete('user_profiles', ['user_id' => $this->userId]);
        }

        // Remove test user
        if ($this->userId) {
            $this->db->delete('users', ['id' => $this->userId]);
        }

        // clear session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }

    public function testProfileImageUploadUpdatesDBAndSession()
    {
        // Create a tiny 1x1 PNG image binary
        $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAr8B9uS0qXwAAAAASUVORK5CYII=';
        $tmp = tempnam(sys_get_temp_dir(), 'pfimg');
        file_put_contents($tmp, base64_decode($pngBase64));

        // Call helper that copies the path into uploads and updates DB/session
        $result = handleProfileUploadFromPath($this->userId, $tmp);

        $this->assertIsArray($result, 'Upload result should be an array');
        $this->assertTrue(!empty($result['success']), 'Upload helper should report success: ' . json_encode($result));

        // DB should have profile_image in user_profiles table
        $row = $this->db->fetchOne('SELECT * FROM user_profiles WHERE user_id = :user_id', ['user_id' => $this->userId]);
        $this->assertNotNull($row, 'user_profiles row should exist for test user');
        $this->assertNotEmpty($row['profile_image'], 'profile_image should be populated in user_profiles table');

        // session should have been updated by helper
        $this->assertTrue(session_status() === PHP_SESSION_ACTIVE || session_start(), 'Session should be available');
        $this->assertArrayHasKey('profile_image', $_SESSION, '$_SESSION should contain profile_image');
        $this->assertEquals($row['profile_image'], $_SESSION['profile_image'], 'Session profile_image should match DB');

        // ensure the uploaded file exists in uploads directory
        $uploadsDir = __DIR__ . '/../backend/auth/uploads/profiles/';
        $this->assertDirectoryExists($uploadsDir, 'Uploads directory must exist');

        // capture path for cleanup
        $this->uploadedFilePath = __DIR__ . '/../..' . $row['profile_image'];
        $this->assertFileExists($this->uploadedFilePath, 'Uploaded file should exist on disk');
    }

    public function testViewProfileReflectsDBValues()
    {
        // Persist extended fields into user_profiles table (canonical for extended data)
        $profileData = [
            'user_id' => $this->userId,
            'phone' => '+90 555 000 0000',
            'home_phone' => '+90 212 000 0000',
            'national_id' => '12345678901',
            'driver_license' => 'A1234567',
            'city' => 'İstanbul',
            'address' => 'Test address 123'
        ];
        
        // Check if user_profiles entry exists, then update or insert
        $existing = $this->db->fetchOne('SELECT user_id FROM user_profiles WHERE user_id = :user_id', ['user_id' => $this->userId]);
        if ($existing) {
            $this->db->update('user_profiles', $profileData, ['user_id' => $this->userId]);
        } else {
            $this->db->insert('user_profiles', $profileData);
        }

        // Fetch the user_profiles row and assert fields match
        $fresh = $this->db->fetchOne('SELECT * FROM user_profiles WHERE user_id = :user_id', ['user_id' => $this->userId]);

        $this->assertNotNull($fresh, 'User profile row should be returned');
        $this->assertEquals($profileData['phone'], $fresh['phone']);
        $this->assertEquals($profileData['home_phone'], $fresh['home_phone']);
        $this->assertEquals($profileData['national_id'], $fresh['national_id']);
        $this->assertEquals($profileData['driver_license'], $fresh['driver_license']);
        $this->assertEquals($profileData['city'], $fresh['city']);
        $this->assertEquals($profileData['address'], $fresh['address']);
    }

    public function testUploadedImagePersistsAfterRefresh()
    {
        // Reuse upload helper test: create an upload
        $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAr8B9uS0qXwAAAAASUVORK5CYII=';
        $tmp = tempnam(sys_get_temp_dir(), 'pfimg');
        file_put_contents($tmp, base64_decode($pngBase64));

        $result = handleProfileUploadFromPath($this->userId, $tmp);
        $this->assertNotEmpty($result['success'], 'Upload should succeed');

        // Simulate a page refresh by fetching DB row again from user_profiles
        $row = $this->db->fetchOne('SELECT profile_image FROM user_profiles WHERE user_id = :user_id', ['user_id' => $this->userId]);
        $this->assertNotNull($row['profile_image'] ?? null, 'profile_image must persist in DB after upload');

        $path = __DIR__ . '/../..' . $row['profile_image'];
        $this->assertFileExists($path, 'Uploaded image file should persist on disk after refresh');

        // capture path for cleanup
        $this->uploadedFilePath = $path;
    }
}
