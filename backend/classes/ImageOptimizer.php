<?php
declare(strict_types=1);

namespace App\Classes;

class ImageOptimizer
{
    private $quality = 85;
    private $maxWidth = 1200;
    private $maxHeight = 1200;

    public function optimize($sourcePath)
    {
        $imageInfo = getimagesize($sourcePath);
        $mime = $imageInfo['mime'];

        switch ($mime) {
            case 'image/jpeg':
                return $this->optimizeJpeg($sourcePath);
            case 'image/png':
                return $this->optimizePng($sourcePath);
            default:
                throw new Exception('Unsupported image type');
        }
    }

    private function optimizeJpeg($sourcePath)
    {
        $image = imagecreatefromjpeg($sourcePath);
        $image = $this->resizeImage($image);

        $optimizedPath = $this->getOptimizedPath($sourcePath);
        imagejpeg($image, $optimizedPath, $this->quality);
        imagedestroy($image);

        return $optimizedPath;
    }

    private function resizeImage($image)
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= $this->maxWidth && $height <= $this->maxHeight) {
            return $image;
        }

        $ratio = min($this->maxWidth / $width, $this->maxHeight / $height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled(
            $resized,
            $image,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $width,
            $height
        );

        return $resized;
    }
}

