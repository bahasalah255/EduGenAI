<?php
use App\Http\Controllers\LessonController;

Route::post('/generate', [LessonController::class, 'generate']);
?>