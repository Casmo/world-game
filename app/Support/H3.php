<?php

namespace App\Support;

use FFI;

/**
 * Thin wrapper over the native libh3 (v4) C library via FFI.
 *
 * Isolates the project's H3 dependency behind one small interface so callers
 * never touch FFI directly (see ADR-0008).
 */
class H3
{
    private FFI $ffi;

    public function __construct(?string $libPath = null)
    {
        $libPath ??= getenv('H3_LIB_PATH') ?: '/opt/homebrew/lib/libh3.dylib';

        $this->ffi = FFI::cdef(<<<'CDEF'
            typedef uint64_t H3Index;
            typedef uint32_t H3Error;
            typedef struct { double lat; double lng; } LatLng;
            double degsToRads(double degrees);
            double radsToDegs(double radians);
            H3Error latLngToCell(const LatLng *g, int res, H3Index *out);
            H3Error cellToLatLng(H3Index h3, LatLng *g);
            H3Error maxGridDiskSize(int k, int64_t *out);
            H3Error gridDisk(H3Index origin, int k, H3Index *out);
            int isValidCell(H3Index h);
        CDEF, $libPath);
    }

    public function latLngToCell(float $lat, float $lng, int $resolution): string
    {
        $coord = $this->ffi->new('LatLng');
        $coord->lat = $this->ffi->degsToRads($lat);
        $coord->lng = $this->ffi->degsToRads($lng);

        $out = $this->ffi->new('H3Index');
        $this->ffi->latLngToCell(FFI::addr($coord), $resolution, FFI::addr($out));

        return $this->toHex($out->cdata);
    }

    /**
     * @return array{0: float, 1: float} [lat, lng] in degrees
     */
    public function cellToLatLng(string $cell): array
    {
        $coord = $this->ffi->new('LatLng');
        $this->ffi->cellToLatLng($this->toIndex($cell), FFI::addr($coord));

        return [
            $this->ffi->radsToDegs($coord->lat),
            $this->ffi->radsToDegs($coord->lng),
        ];
    }

    /**
     * All cells within k rings of the origin, including the origin.
     *
     * @return array<int, string>
     */
    public function disk(string $cell, int $k): array
    {
        $size = $this->ffi->new('int64_t');
        $this->ffi->maxGridDiskSize($k, FFI::addr($size));
        $max = $size->cdata;

        $out = $this->ffi->new("H3Index[$max]");
        $this->ffi->gridDisk($this->toIndex($cell), $k, $out);

        $cells = [];
        for ($i = 0; $i < $max; $i++) {
            if ($out[$i] !== 0) {
                $cells[] = $this->toHex($out[$i]);
            }
        }

        return $cells;
    }

    /**
     * The six cells directly adjacent to the given cell (origin excluded).
     *
     * @return array<int, string>
     */
    public function neighbors(string $cell): array
    {
        return array_values(
            array_filter($this->disk($cell, 1), fn (string $c) => $c !== $cell)
        );
    }

    public function isValidCell(string $cell): bool
    {
        return $this->ffi->isValidCell($this->toIndex($cell)) === 1;
    }

    /**
     * Convert an H3 index hex string (the boundary representation) to the
     * 64-bit integer libh3 expects.
     */
    private function toIndex(string $cell): int
    {
        return (int) hexdec($cell);
    }

    /**
     * Convert a 64-bit H3 index back to its canonical hex string.
     */
    private function toHex(int $index): string
    {
        return sprintf('%x', $index);
    }
}
