<?php

namespace App\Services;

use RuntimeException;

class FramePlacementDetector
{
    private const STRICT_TRANSPARENT_ALPHA = 127;
    private const RELAXED_TRANSPARENT_ALPHA = 120;

    private const MIN_WIDTH = 20;
    private const MIN_HEIGHT = 20;
    private const MIN_AREA_RATIO = 0.0025;
    private const MIN_FILL_RATIO = 0.60;

    /**
     * @return array<int, array{slot:int,x:int,y:int,width:int,height:int,rotation:int}>
     */
    public function detect(string $pngPath): array
    {
        if (!extension_loaded('gd')) {
            throw new RuntimeException(
                'PHP GD extension diperlukan untuk mendeteksi lubang frame.'
            );
        }

        $info = getimagesize($pngPath);
        if ($info === false || ($info[2] ?? null) !== IMAGETYPE_PNG) {
            throw new RuntimeException('File frame bukan PNG yang valid.');
        }

        $width = (int) $info[0];
        $height = (int) $info[1];

        /*
         * Some valid PNGs contain stale/incorrect ICC (iCCP/cHRM) metadata.
         * libpng reports these as warnings. They do not prevent GD from
         * decoding the image, so temporarily suppress the warning while
         * calling imagecreatefrompng().
         */
        $previousReportingLevel = error_reporting();
        error_reporting($previousReportingLevel & ~E_WARNING);

        try {
            $image = imagecreatefrompng($pngPath);
        } finally {
            error_reporting($previousReportingLevel);
        }

        if ($image === false) {
            throw new RuntimeException(
                'PNG tidak dapat dibaca oleh GD.'
            );
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        try {
            $strictMask = $this->buildTransparentMask(
                $image,
                $width,
                $height,
                self::STRICT_TRANSPARENT_ALPHA
            );

            $placements = $this->findEnclosedRegions(
                $strictMask,
                $width,
                $height
            );

            if ($placements !== []) {
                return $placements;
            }

            $relaxedMask = $this->buildTransparentMask(
                $image,
                $width,
                $height,
                self::RELAXED_TRANSPARENT_ALPHA
            );

            $placements = $this->findEnclosedRegions(
                $relaxedMask,
                $width,
                $height
            );

            if ($placements !== []) {
                return $placements;
            }

            $opaqueMask = $this->buildOpaqueMask(
                $image,
                $width,
                $height
            );

            return $this->findEnclosedRegions(
                $opaqueMask,
                $width,
                $height
            );
        } finally {
            imagedestroy($image);
        }
    }

    private function buildTransparentMask(
        $image,
        int $width,
        int $height,
        int $minimumGdAlpha
    ): string {
        $mask = str_repeat("\0", $width * $height);

        for ($y = 0; $y < $height; $y++) {
            $rowOffset = $y * $width;

            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F;

                if ($alpha >= $minimumGdAlpha) {
                    $mask[$rowOffset + $x] = "\1";
                }
            }
        }

        return $mask;
    }

    private function buildOpaqueMask(
        $image,
        int $width,
        int $height
    ): string {
        $mask = str_repeat("\0", $width * $height);

        for ($y = 0; $y < $height; $y++) {
            $rowOffset = $y * $width;

            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F;

                if ($alpha < self::RELAXED_TRANSPARENT_ALPHA) {
                    $mask[$rowOffset + $x] = "\1";
                }
            }
        }

        return $mask;
    }

    /**
     * @return array<int, array{slot:int,x:int,y:int,width:int,height:int,rotation:int}>
     */
    private function findEnclosedRegions(
        string $mask,
        int $width,
        int $height
    ): array {
        $visited = str_repeat("\0", $width * $height);
        $regions = [];
        $canvasArea = $width * $height;

        for ($y = 0; $y < $height; $y++) {
            $rowOffset = $y * $width;

            for ($x = 0; $x < $width; $x++) {
                $start = $rowOffset + $x;

                if ($mask[$start] !== "\1" || $visited[$start] === "\1") {
                    continue;
                }

                $queue = new \SplQueue();
                $queue->enqueue($start);
                $visited[$start] = "\1";

                $minX = $maxX = $x;
                $minY = $maxY = $y;
                $area = 0;
                $touchesBorder = false;

                while (!$queue->isEmpty()) {
                    $index = $queue->dequeue();
                    $cx = $index % $width;
                    $cy = intdiv($index, $width);
                    $area++;

                    if (
                        $cx === 0 ||
                        $cy === 0 ||
                        $cx === $width - 1 ||
                        $cy === $height - 1
                    ) {
                        $touchesBorder = true;
                    }

                    $minX = min($minX, $cx);
                    $maxX = max($maxX, $cx);
                    $minY = min($minY, $cy);
                    $maxY = max($maxY, $cy);

                    for ($dy = -1; $dy <= 1; $dy++) {
                        for ($dx = -1; $dx <= 1; $dx++) {
                            if ($dx === 0 && $dy === 0) {
                                continue;
                            }

                            $nx = $cx + $dx;
                            $ny = $cy + $dy;

                            if (
                                $nx < 0 ||
                                $nx >= $width ||
                                $ny < 0 ||
                                $ny >= $height
                            ) {
                                continue;
                            }

                            $next = $ny * $width + $nx;

                            if (
                                $mask[$next] === "\1" &&
                                $visited[$next] !== "\1"
                            ) {
                                $visited[$next] = "\1";
                                $queue->enqueue($next);
                            }
                        }
                    }
                }

                if ($touchesBorder) {
                    continue;
                }

                $boxWidth = $maxX - $minX + 1;
                $boxHeight = $maxY - $minY + 1;
                $boxArea = $boxWidth * $boxHeight;

                $fillRatio = $boxArea > 0
                    ? $area / $boxArea
                    : 0.0;

                $areaRatio = $canvasArea > 0
                    ? $area / $canvasArea
                    : 0.0;

                if (
                    $boxWidth < self::MIN_WIDTH ||
                    $boxHeight < self::MIN_HEIGHT ||
                    $areaRatio < self::MIN_AREA_RATIO ||
                    $fillRatio < self::MIN_FILL_RATIO
                ) {
                    continue;
                }

                $regions[] = [
                    'slot' => 0,
                    'x' => $minX,
                    'y' => $minY,
                    'width' => $boxWidth,
                    'height' => $boxHeight,
                    'rotation' => 0,
                ];
            }
        }

        usort($regions, static function (array $a, array $b): int {
            if ($a['y'] === $b['y']) {
                return $a['x'] <=> $b['x'];
            }

            return $a['y'] <=> $b['y'];
        });

        foreach ($regions as $index => &$region) {
            $region['slot'] = $index + 1;
        }
        unset($region);

        return $regions;
    }
}
