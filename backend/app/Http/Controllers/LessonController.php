<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AIService;

class LessonController extends Controller
{
    public function generate(Request $request, AIService $ai)
    {
        $request->validate([
            'topic' => 'required|string'
        ]);

        return response()->json(
            $ai->generate($request->topic)
        );
    }
}
