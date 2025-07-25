<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthenticationController;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Cloudinary\Cloudinary;
use Illuminate\Http\Request;

require __DIR__ . '/auth.php'; //edit image DONE       EDIT THE CATCH BLOCK TO PUT LOG!!
require __DIR__ . '/alumnilist.php';
require __DIR__ . '/announcement.php'; //edit image DONE
require __DIR__ . '/institute_course.php'; //edit image DONE
require __DIR__ . '/post.php'; //edit image DONE
require __DIR__ . '/survey.php';
require __DIR__ . '/accounts.php'; //edit image NOT SURE
require __DIR__ . '/profile.php'; //edit image DONE

// FOR TESTING ONLY
Route::get('/qr-test', function () {
    return QrCode::size(300)->generate('https://example.com');
});

Route::post('/upload-image', function (Request $request) {
    $request->validate(['image' => 'required|image']);

    $cloudinary = new Cloudinary();

    $uploadResult = $cloudinary->uploadApi()->upload($request->file('image')->getRealPath());

    // Create URL with f_auto,q_auto transformations
    $optimizedUrl = $cloudinary->image($uploadResult['public_id'])
                              ->format('auto')
                              ->quality('auto')
                              ->toUrl();

    return response()->json([
        'original_url' => $uploadResult['secure_url'],
        'optimized_url' => $optimizedUrl,
    ]);
});