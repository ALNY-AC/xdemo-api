<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use App\Model\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    use ResponseJson;

    public function save(Request $request)
    {
        $data = $request->all();
        $data['type'] = 3;
        if ($request->filled('id')) {

            $result = Feedback::where('id', $request->input('id'))
                ->update($data);

            if ($result >= 0) {
                return $this->jsonData(1, '保存成功', $result);
            } else {
                return $this->jsonData(-1, '保存失败', $result);
            }
        } else {
            $feedback = new Feedback($data);
            $feedback->user_id = $request->get('jwt')->id;
            $result = $feedback->save();
            if ($result) {
                return $this->jsonData(1, '保存成功', $result);
            } else {
                return $this->jsonData(-1, '保存失败', $result);
            }
        }
    }
}
