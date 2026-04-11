<?php

namespace App\Http\Controllers;

class InitiativeController extends Controller
{
    public function __invoke()
    {
        return view('initiative.index');
    }
}
