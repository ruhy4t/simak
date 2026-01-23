<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TemporaryUploadController extends Controller
{
    /**
     * Handle temporary file upload.
     * Stores file in 'temp_uploads' directory and returns the path.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:5120', // Max 5MB
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();

            // Store in a local temp disk or specific folder
            // Using 'local' disk which usually points to storage/app
            $path = $file->storeAs('temp_uploads', $filename, 'local');

            return response()->json([
                'success' => true,
                'path' => $path, // e.g., "temp_uploads/xyz.pdf"
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize()
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No file uploaded'], 400);
    }
}
