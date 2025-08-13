<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Conversation;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;

class ConversationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'admin_id' => 'required|exists:admins,id',
            'last_message' => 'sometimes|nullable|string|max:255',
        ]);

        $userId = Auth::id();

        // Check if conversation exists
        $conversation = Conversation::where('user_id', $userId)
            ->where('admin_id', $request->admin_id)
            ->first();

        if ($conversation) {
            // Update last_message and updated_at
            if ($request->has('last_message')) {
                $conversation->update([
                    'last_message' => $request->last_message,
                    'updated_at' => now(),
                ]);
            } else {
                $conversation->touch(); // Just update the timestamp
            }
            

            return response()->json($conversation); // return the conversation object
        } else {
            // Create a new conversation
            $conversation = Conversation::create([
                'id' => (string) Str::uuid(),
                'admin_id' => $request->admin_id,
                'user_id' => $userId,
                'last_message' => $request->last_message,
            ]);

            return response()->json($conversation, 201);
        }
    }

    public function displayAdmins()
    {
        $admins = Admin::all();
        return response()->json($admins);
    }

    public function getAllConversationsforUser()
    {
        $userId = Auth::id(); // Get the authenticated user's ID
        $conversations = Conversation::where('user_id', $userId)->with('admin')->get();

        return response()->json($conversations);
    }

    public function getAllConversationsforAdmin()
    {
        $adminId = Auth::id(); // Get the authenticated admin's ID
        $conversations = Conversation::where('admin_id', $adminId)->with('user')->get();

        return response()->json($conversations);
    }

    public function showConversation($id)
    {
        $conversation = Conversation::with('admin', 'user')->findOrFail($id);
        return response()->json($conversation);
    }
}
