<?php
declare(strict_types=1);

namespace App\Classes;

class CacheManager
{
    private $cacheDir;
    private $defaultTTL;

    public function __construct($cacheDir = '../cache', $defaultTTL = 3600)
    {
        $this->cacheDir = $cacheDir;
        $this->defaultTTL = $defaultTTL;

        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
    }

    public function get($key)
    {
        $filename = $this->getCacheFilename($key);

        if (!file_exists($filename)) {
            return null;
        }

        $content = file_get_contents($filename);
        $data = json_decode($content, true);

        if (!$data || time() > $data['expires']) {
            unlink($filename);
            return null;
        }

        return $data['content'];
    }

    public function set($key, $value, $ttl = null)
    {
        $filename = $this->getCacheFilename($key);
        $ttl = $ttl ?? $this->defaultTTL;

        $data = [
            'expires' => time() + $ttl,
            'content' => $value
        ];

        file_put_contents($filename, json_encode($data));
    }

    public function delete($key)
    {
        $filename = $this->getCacheFilename($key);
        if (file_exists($filename)) {
            unlink($filename);
        }
    }

    public function flush()
    {
        $files = glob($this->cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private function getCacheFilename($key)
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
}

// Usage example:
/*
$cache = new CacheManager();

// Caching page content
$pageContent = $cache->get('page_' . $pageId);
if ($pageContent === null) {
    $pageContent = // ... fetch from database
    $cache->set('page_' . $pageId, $pageContent);
}

// Clear cache when content is updated
$cache->delete('page_' . $pageId);
*/

