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

];
