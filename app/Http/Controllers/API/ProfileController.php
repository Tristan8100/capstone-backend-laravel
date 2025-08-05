<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Cloudinary\Cloudinary;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

use App\Models\User;
class ProfileController extends Controller
{
    public function addPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:20480',
        ]);

        $user = Auth::user();

        // Delete old photo from Cloudinary if stored
        if ($user->profile_path && str_starts_with($user->profile_path, 'https://res.cloudinary.com')) {
            $publicId = pathinfo(parse_url($user->profile_path, PHP_URL_PATH), PATHINFO_FILENAME);
            (new Cloudinary())->uploadApi()->destroy('profile/' . $publicId);
        }

        $file = $request->file('photo');
        $manager = new ImageManager(new Driver());

        // Resize by image manager
        $image = $manager->read($file->getRealPath())->cover(300, 300)->toJpeg(); //put number remember

        // Upload resized image directly from memory
        $cloudinary = new Cloudinary();
        $upload = $cloudinary->uploadApi()->upload($image->toDataUri(), [
            'folder' => 'profile',
            'public_id' => 'user_' . $user->id . '_' . time(),
            'overwrite' => true,
        ]);

        $secureUrl = $upload['secure_url'];

        // Update DB with Cloudinary image URL
        User::where('id', $user->id)->update([
            'profile_path' => $secureUrl,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Photo uploaded to Cloudinary.',
            'profile_path' => $secureUrl,
        ]);
    }

    // Admin specific method to add photo
    public function addPhotoAdmin(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:20480',
        ]);

        $user = Auth::user();

        // Delete old photo from Cloudinary if stored
        if ($user->profile_path && str_starts_with($user->profile_path, 'https://res.cloudinary.com')) {
            $publicId = pathinfo(parse_url($user->profile_path, PHP_URL_PATH), PATHINFO_FILENAME);
            (new Cloudinary())->uploadApi()->destroy('profile/' . $publicId);
        }

        $file = $request->file('photo');
        $manager = new ImageManager(new Driver());

        // Resize by image manager
        $image = $manager->read($file->getRealPath())->cover(300, 300)->toJpeg(); //put number remember

        // Upload resized image directly from memory
        $cloudinary = new Cloudinary();
        $upload = $cloudinary->uploadApi()->upload($image->toDataUri(), [
            'folder' => 'profile',
            'public_id' => 'user_' . $user->id . '_' . time(),
            'overwrite' => true,
        ]);

        $secureUrl = $upload['secure_url'];

        // Update DB with Cloudinary image URL
        Admin::where('id', $user->id)->update([
            'profile_path' => $secureUrl,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Photo uploaded to Cloudinary.',
            'profile_path' => $secureUrl,
        ]);
    }
}
