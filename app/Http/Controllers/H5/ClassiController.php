<?php

namespace App\Http\Controllers\H5;
// @todo: 这里是要生成类的命名空间

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use App\Model\Classi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ClassiController extends Controller
{  // @todo AuthController 这里是要生成的类名字

    use ResponseJson;

    public function list(Request $request)
    {
        $DB = Classi::where('store_id', $request->input('store_id'))
            ->where('is_up', 1)
            ->where('data_state', 1)
            ->orderBy('sort', 'desc');

        $result = $DB->get();

        $data['list'] = $result;
        $data['total'] = $result->count();
        return $this->jsonData(1, 'OK', $data);
    }
}
