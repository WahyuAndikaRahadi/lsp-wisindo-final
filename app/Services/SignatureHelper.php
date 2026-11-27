<?php

namespace App\Services;

class SignatureHelper
{
    /**
     * Auto-trim empty transparent or white borders from a signature image
     * and return a transparent PNG base64 data URI.
     */
    public static function trimSignature(?string $source, int $padding = 10): ?string
    {
        if (empty($source)) {
            return null;
        }

        if (! extension_loaded('gd')) {
            return $source;
        }

        $binary = null;
        if (str_starts_with($source, 'data:')) {
            $parts = explode(',', $source, 2);
            $binary = base64_decode($parts[1] ?? '');
        } elseif (is_file($source)) {
            $binary = @file_get_contents($source);
        } else {
            $binary = $source;
        }

        if (! $binary) {
            return $source;
        }

        $im = @imagecreatefromstring($binary);
        if (! $im) {
            return $source;
        }

        $w = imagesx($im);
        $h = imagesy($im);
        $minX = $w;
        $minY = $h;
        $maxX = 0;
        $maxY = 0;

        // Scan pixels with step 2 for performance
        for ($y = 0; $y < $h; $y += 2) {
            for ($x = 0; $x < $w; $x += 2) {
                $rgba = imagecolorat($im, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;

                // In GD, alpha is 0 (opaque) to 127 (fully transparent)
                if ($alpha < 120) {
                    $r = ($rgba >> 16) & 0xFF;
                    $g = ($rgba >> 8) & 0xFF;
                    $b = $rgba & 0xFF;

                    // Exclude near-white backgrounds
                    if ($r < 240 || $g < 240 || $b < 240) {
                        if ($x < $minX) {
                            $minX = $x;
                        }
                        if ($x > $maxX) {
                            $maxX = $x;
                        }
                        if ($y < $minY) {
                            $minY = $y;
                        }
                        if ($y > $maxY) {
                            $maxY = $y;
                        }
                    }
                }
            }
        }

        // If no content found or dimensions invalid, return original
        if ($maxX <= $minX || $maxY <= $minY) {
            imagedestroy($im);

            return $source;
        }

        $cropX = max(0, $minX - $padding);
        $cropY = max(0, $minY - $padding);
        $cropW = min($w - $cropX, ($maxX - $minX) + ($padding * 2));
        $cropH = min($h - $cropY, ($maxY - $minY) + ($padding * 2));

        $crop = imagecrop($im, ['x' => $cropX, 'y' => $cropY, 'width' => $cropW, 'height' => $cropH]);
        imagedestroy($im);

        if (! $crop) {
            return $source;
        }

        // Create transparent PNG canvas
        $final = imagecreatetruecolor($cropW, $cropH);
        imagealphablending($final, false);
        imagesavealpha($final, true);
        $transparent = imagecolorallocatealpha($final, 0, 0, 0, 127);
        imagefilledrectangle($final, 0, 0, $cropW, $cropH, $transparent);

        for ($y = 0; $y < $cropH; $y++) {
            for ($x = 0; $x < $cropW; $x++) {
                $rgba = imagecolorat($crop, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;

                if ($alpha < 120 && ($r < 240 || $g < 240 || $b < 240)) {
                    // Normalize stroke to dark ink with preserved alpha
                    $color = imagecolorallocatealpha($final, min($r, 40), min($g, 40), min($b, 40), max(0, (int) ($alpha * 0.8)));
                    imagesetpixel($final, $x, $y, $color);
                }
            }
        }

        imagedestroy($crop);

        ob_start();
        imagepng($final, null, 8);
        $out = ob_get_clean();
        imagedestroy($final);

        return 'data:image/png;base64,'.base64_encode($out);
    }
}
