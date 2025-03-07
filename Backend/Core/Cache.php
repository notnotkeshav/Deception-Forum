<?php

namespace Backend\Core;

class Cache
{
    private $cacheDir;

    public function __construct($cacheDir = __DIR__ . '/cache/')
    {
        $this->cacheDir = $cacheDir;
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    public function get($key)
    {
        $filePath = $this->cacheDir . md5($key) . '.cache';
        if (file_exists($filePath)) {
            $data = file_get_contents($filePath);
            return unserialize($data);
        }
        return false;
    }

    public function set($key, $value, $expiration = 3600)
    {
        $filePath = $this->cacheDir . md5($key) . '.cache';
        $data = [
            'value' => $value,
            'expiration' => time() + $expiration
        ];
        file_put_contents($filePath, serialize($data));
    }

    public function delete($key)
    {
        $filePath = $this->cacheDir . md5($key) . '.cache';
        if (file_exists($filePath)) {
            unlink($filePath);
            return true;
        }
        return false;
    }

    public function clearExpired()
    {
        foreach (glob($this->cacheDir . '*.cache') as $file) {
            $data = unserialize(file_get_contents($file));
            if ($data['expiration'] < time()) {
                unlink($file);
            }
        }
    }
}
