<?php

namespace App\Http\Controllers\API;
use App\Models\AnnouncementImage;
use App\Models\Announcement;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function index()
    {
        return Announcement::with('images', 'comments.replies')->latest()->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $announcement = Announcement::create([
            'admin_id' => Auth::guard('admin')->id(),
            'title' => $request->title,
            'content' => $request->content,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $imageFile) {
                $filename = time() . '_' . $imageFile->getClientOriginalName();
                $imageFile->move(public_path('announcement_images'), $filename);

                AnnouncementImage::create([
                    'announcement_id' => $announcement->id,
                    'image_name' => $filename,
                    'image_file' => 'announcement_images/' . $filename,
                ]);
            }
        }

        return response()->json(['message' => 'Announcement created successfully.'], 201);
    }

    public function show($id)
    {
        return Announcement::with('images', 'comments.replies')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $announcement->update($request->only(['title', 'content']));

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $imageFile) {
                $filename = time() . '_' . $imageFile->getClientOriginalName();
                $imageFile->move(public_path('announcement_images'), $filename);

                AnnouncementImage::create([
                    'announcement_id' => $announcement->id,
                    'image_name' => $filename,
                    'image_file' => 'announcement_images/' . $filename,
                ]);
            }
        }

        return response()->json(['message' => 'Announcement updated.']);
    }

    public function destroy($id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->delete();

        return response()->json(['message' => 'Announcement deleted.']);
    }
}
