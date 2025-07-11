<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthenticationController;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
require __DIR__ . '/auth.php';
require __DIR__ . '/alumnilist.php';
require __DIR__ . '/announcement.php';
require __DIR__ . '/institute_course.php';
require __DIR__ . '/post.php';
require __DIR__ . '/survey.php';

// FOR TESTING ONLY
Route::get('/qr-test', function () {
    return QrCode::size(300)->generate('https://example.com');
});