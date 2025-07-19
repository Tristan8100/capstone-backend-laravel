<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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

        $directory = public_path('profile');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Delete old photo if exists
        if ($user->profile_path) {
            $oldPhotoPath = public_path($user->profile_path);
            if (File::exists($oldPhotoPath)) {
                File::delete($oldPhotoPath);
            }

            // Clear old path
            User::where('id', $user->id)->update(['profile_path' => null]);
        }

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $relativePath = '/profile/' . $filename;
            $fullPath = $directory . DIRECTORY_SEPARATOR . $filename;

            // Move the uploaded file to /public/profile
            $file->move($directory, $filename);

            // Resize with Intervention Image
            $manager = new ImageManager(new Driver());
            $image = $manager->read($fullPath);
            $image->cover(300, 300);
            $image->save($fullPath);

            // Update DB
            User::where('id', $user->id)->update(['profile_path' => $relativePath]);

            return response()->json([
                'success' => true,
                'message' => 'Photo uploaded successfully.',
                'profile_path' => $relativePath,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No photo uploaded.',
        ], 400);
    }
}
