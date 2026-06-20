<?php

return [

    /*
    |--------------------------------------------------------------------------
    | libh3 Shared Library Path
    |--------------------------------------------------------------------------
    |
    | Absolute path to the native libh3 (v4) shared library that App\Support\H3
    | binds to via FFI (see ADR-0008). This must be installed in every
    | environment — macOS/Herd via `brew install h3`, Linux via the distro
    | package or a built library.
    |
    */

    'lib_path' => env('H3_LIB_PATH', '/opt/homebrew/lib/libh3.dylib'),

    /*
    |--------------------------------------------------------------------------
    | Tile Resolution
    |--------------------------------------------------------------------------
    |
    | The H3 resolution used for world Tiles (~25–50 km cells per ADR-0001).
    |
    */

    'resolution' => (int) env('H3_RESOLUTION', 7),

    /*
    |--------------------------------------------------------------------------
    | World-map View
    |--------------------------------------------------------------------------
    |
    | The default centre of the world-map view and how many rings of Tiles to
    | reveal around it.
    |
    */

    'default_center' => [
        'lat' => (float) env('WORLD_MAP_LAT', 52.3676),
        'lng' => (float) env('WORLD_MAP_LNG', 4.9041),
    ],

    'view_ring' => (int) env('WORLD_MAP_RING', 2),

    /*
    |--------------------------------------------------------------------------
    | Spawn Search
    |--------------------------------------------------------------------------
    |
    | How many rings out from the spawn centre to search for an unowned Tile
    | when a new Team founds and claims its starting Tile.
    |
    */

    'spawn_ring' => (int) env('WORLD_SPAWN_RING', 8),

];
