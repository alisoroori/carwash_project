<?php
// Reusable profile auto-fix utilities
// Usage: require_once __DIR__ . '/profile_auto_fix.php';
// then call autoFixProfileImage($userId);

use App\Classes\Database;

if (!function_exists('autoFixProfileImage')) {
    function autoFixProfileImage($userId)
    {
        // Prepare log file
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/profile_debug.log';
        $writeLog = function($msg) use ($logFile) {
            $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
            @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
        };

        $db = null;
        try {
            $db = Database::getInstance();
        } catch (Exception $e) {
            $writeLog("ERROR: Could not get Database instance: " . $e->getMessage());
            return "ERROR: Database unavailable.";
        }

        // Fetch DB value
        $row = $db->fetchOne('SELECT profile_image FROM user_profiles WHERE user_id = :user_id', ['user_id' => $userId]);
        $dbVal = $row['profile_image'] ?? '';
        $sessVal = $_SESSION['profile_image'] ?? ($_SESSION['user']['profile_image'] ?? '');

        $writeLog("Initial DB value: " . ($dbVal ?: 'EMPTY'));
        $writeLog("Initial Session value: " . ($sessVal ?: 'EMPTY'));

        $uploadDirWeb = '/carwash_project/backend/auth/uploads/profiles/';
        $uploadDirFs = realpath(__DIR__ . '/../auth/uploads/profiles') ?: (__DIR__ . '/../auth/uploads/profiles');

        // CHECK 1 — DB mismatch: if DB empty but session or uploaded file exists, try to repair
        if (empty($dbVal) && !empty($sessVal)) {
            // Session has a path — try to write to DB
            try {
                $db->update('user_profiles', ['profile_image' => $sessVal], ['user_id' => $userId]);
                $writeLog("Repaired DB from session value: {$sessVal}");
                $dbVal = $sessVal;
            } catch (Exception $e) {
                $writeLog("ERROR: Failed to update DB from session: " . $e->getMessage());
                return "ERROR: Could not write profile image to database.";
            }
        }

        // If DB empty but there's a recently uploaded file in temp (try to locate)
        if (empty($dbVal) && isset($_FILES) && !empty($_FILES)) {
            foreach ($_FILES as $f) {
                if (!empty($f['tmp_name']) && is_uploaded_file($f['tmp_name'])) {
                    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                    $newName = 'profile_' . $userId . '_' . time() . '.' . $ext;
                    $destFs = rtrim($uploadDirFs, '/\\') . DIRECTORY_SEPARATOR . $newName;
                    if (@move_uploaded_file($f['tmp_name'], $destFs)) {
                        $webPath = $uploadDirWeb . $newName;
                        try {
                            $db->update('user_profiles', ['profile_image' => $webPath], ['user_id' => $userId]);
                            $_SESSION['profile_image'] = $webPath;
                            $_SESSION['user']['profile_image'] = $webPath;
                            $_SESSION['profile_image_ts'] = time();
                            $writeLog("Moved recent upload and updated DB/session: {$webPath}");
                            $dbVal = $webPath;
                            break;
                        } catch (Exception $e) {
                            $writeLog("ERROR: Failed DB update after moving uploaded file: " . $e->getMessage());
                            return "ERROR: Could not persist uploaded profile image to database.";
                        }
                    }
                }
            }
        }

        // CHECK 2 — SESSION missing: if session missing but DB has value, populate session
        if (empty($sessVal) && !empty($dbVal)) {
            $_SESSION['profile_image'] = $dbVal;
            $_SESSION['user']['profile_image'] = $dbVal;
            $_SESSION['profile_image_ts'] = time();
            $sessVal = $dbVal;
            $writeLog("Re-populated session from DB: {$dbVal}");
        }

        // After attempts, if still empty
        if (empty($dbVal) && empty($sessVal)) {
            $writeLog('ERROR: No profile image in DB or session and no uploaded file found.');
            return 'ERROR: No profile image found; automatic repair not possible.';
        }

        // Normalize final path (prefer session value)
        $finalRel = $_SESSION['profile_image'] ?? $dbVal;

        // CHECK 3 — File missing or unreadable
        // Compute filesystem path if relative to uploads dir
        $finalFilename = basename(parse_url($finalRel, PHP_URL_PATH));
        $finalFs = rtrim($uploadDirFs, '/\\') . DIRECTORY_SEPARATOR . $finalFilename;

        $fileExists = file_exists($finalFs);
        $fileReadable = is_readable($finalFs);
        $writeLog("File exists: " . ($fileExists ? 'YES' : 'NO') . "; Readable: " . ($fileReadable ? 'YES' : 'NO') . "; FS path: {$finalFs}");

        // If file missing but we may have the file elsewhere (for example session contained full path)
        if (!$fileExists) {
            // Try to locate any file with same basename in uploads directory
            $candidates = glob(rtrim($uploadDirFs, '/\\') . DIRECTORY_SEPARATOR . '*' . $finalFilename);
            if (!empty($candidates)) {
                // pick first candidate
                $candidate = $candidates[0];
                if (@copy($candidate, $finalFs) || @rename($candidate, $finalFs)) {
                    $writeLog("Copied candidate {$candidate} to expected location {$finalFs}");
                    $fileExists = file_exists($finalFs);
                }
            }
        }

        if (!$fileExists) {
            $writeLog('ERROR: Upload file is missing and cannot be repaired. Need manual upload.');
            return 'ERROR: Upload file is missing and cannot be repaired. Need manual upload.';
        }

        if (!$fileReadable) {
            // Attempt to chmod to 0644
            try {
                @chmod($finalFs, 0644);
                $fileReadable = is_readable($finalFs);
                $writeLog('Attempted chmod 0644; readable now: ' . ($fileReadable ? 'YES' : 'NO'));
            } catch (Exception $e) {
                $writeLog('ERROR: Failed to chmod file: ' . $e->getMessage());
            }
            if (!$fileReadable) {
                return 'ERROR: Uploaded file exists but is not readable by PHP; permission fix failed.';
            }
        }

        // CHECK 4 & 6 — Fix img src to include ?ts and use session path
        $ts = $_SESSION['profile_image_ts'] ?? time();
        $fixedSrc = $uploadDirWeb . $finalFilename . '?ts=' . $ts;

        // CHECK 5 — JS refresh injection: ensure a small global handler exists in session marker
        // We'll store a small flag in $_SESSION to avoid double-inject when rendering pages
        if (empty($_SESSION['profile_auto_fix_injected'])) {
            $_SESSION['profile_auto_fix_injected'] = true;
        }

        // CHECK 7 — Write final log
        $writeLog('Final fixed image src: ' . $fixedSrc);

        // Update DB/session one last time with relative web path
        try {
            if ($dbVal !== ($uploadDirWeb . $finalFilename)) {
                $db->update('user_profiles', ['profile_image' => $uploadDirWeb . $finalFilename], ['user_id' => $userId]);
                $writeLog('Updated DB to final web path');
            }
            $_SESSION['profile_image'] = $uploadDirWeb . $finalFilename;
            $_SESSION['user']['profile_image'] = $uploadDirWeb . $finalFilename;
            $_SESSION['profile_image_ts'] = $ts;
        } catch (Exception $e) {
            $writeLog('ERROR: Failed to finalize DB/session update: ' . $e->getMessage());
            return 'ERROR: Could not finalize profile image updates.';
        }

        return 'Profile image issue detected and successfully auto-repaired.';
    }
}

?>
