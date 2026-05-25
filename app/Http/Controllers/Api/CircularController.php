<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CircularResource;
use App\Models\Circular;
use Illuminate\Http\Request;

class CircularController extends Controller
{
    public function index()
    {
        return CircularResource::collection(
            Circular::latest('fecha_publicacion')->get()
        );
    }
}
