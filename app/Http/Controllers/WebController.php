<?php

namespace App\Http\Controllers;

class WebController extends Controller {
    public function rsOrderPrint() {
        return view('rs.rsOrderPrint');
    }
}