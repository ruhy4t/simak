<?php

return [
    /*
    | Only script-free GET pages belong here. Complex forms, uploads,
    | downloads, and pages with inline scripts retain full navigation.
    */
    'safe_routes' => [
        'dashboard',
        'satpen.index',
        'sekolah.profile',
        'laporan.index',
        'profile.index',
        'users.manage',
        'admin.regions.index',
    ],

    'safe_views' => [
        'resources/views/dashboard/index.blade.php',
        'resources/views/satpen/index.blade.php',
        'resources/views/satpen/profile.blade.php',
        'resources/views/laporan/index.blade.php',
        'resources/views/profile/index.blade.php',
        'resources/views/users/manage.blade.php',
        'resources/views/admin/regions/index.blade.php',
    ],
];
