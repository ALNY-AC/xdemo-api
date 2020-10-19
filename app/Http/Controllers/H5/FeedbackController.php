<?php

namespace App\Http\Controllers\H5;

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use App\Model\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    use ResponseJson;

    public function save(Request $request)
    {

        if ($request->filled('id')) {

            $result = Feedback::where('id', $request->input('id'))
                ->update($request->all());

            if ($result >= 0) {
                return $this->jsonData(1, '保存成功', $result);
            } else {
                return $this->jsonData(-1, '保存失败', $result);
            }
        } else {
            $feedback = new Feedback($request->all());
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
