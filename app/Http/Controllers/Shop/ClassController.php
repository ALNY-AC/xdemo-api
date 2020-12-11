<?php

namespace App\Http\Controllers\Shop;
// @todo: 这里是要生成类的命名空间

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use Illuminate\Http\Request;
use App\Listeners\Random;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ClassController extends Controller
{
    public function list(Request $request)
    {
        return $request->toArray();
    }
}
