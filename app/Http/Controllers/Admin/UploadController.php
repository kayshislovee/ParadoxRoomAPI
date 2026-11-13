<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UploadController extends Controller
{
    /**
     * Directory upload yang diizinkan (disimpan di disk `public`).
     */
    private const ALLOWED_DIRECTORIES = [
        'achievements',
        'avatars',
        'hotspots',
        'treasures',
        'backgrounds',
        'objects',
        'notes',
        'uploads',
    ];

    /**
     * POST /admin/uploads
     * Menerima file gambar dari halaman admin Filament dan mengembalikan
     * path yang bisa langsung dipakai sebagai src (dicoresolve oleh game
     * menjadi /storage/<path>).
     */
    public function store(Request $request): JsonResponse
    {
        if (! $request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Validasi manual supaya error selalu dirender sebagai JSON 422,
        // bukan redirect HTML (handler exception app ini redirect untuk route non-api).
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'image' => ['required', 'image', 'mimes:png,jpg,jpeg,webp,gif,svg,avif', 'max:8192'],
            'directory' => ['nullable', 'string', Rule::in(self::ALLOWED_DIRECTORIES)],
        ], [
            'image.image' => 'File harus berupa gambar.',
            'directory.in' => 'Direktori upload tidak diizinkan.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $validated = $validator->validated();

        $directory = $validated['directory'] ?? 'uploads';

        $path = $request->file('image')->store($directory, 'public');

        return response()->json([
            'path' => $path,                       // mis. "avatars/abc123.png"
            'url' => Storage::disk('public')->url($path), // mis. "/storage/avatars/abc123.png"
        ]);
    }
}
