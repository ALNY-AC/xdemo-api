<?php

namespace App\Http\Controllers\Ctos;

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

    public function info(Request $request)
    {
        $feedback = Feedback::find($request->input('id'));

        return $this->jsonData(1, 'Ok', $feedback);
    }

    public function list(Request $request)
    {
        $feedback =  new Feedback();

        if ($request->filled('content')) {
            $feedback = $feedback->where('content', 'like', '%' . $request->input('content') . '%');
        }

        $total = $feedback->count();
        if ($request->filled('page')) {
            $feedback = $feedback->offset(($request->input('page', 1) - 1) * $request->input('page_size', 10));
        }
        if ($request->filled('page_size')) {
            $feedback = $feedback->limit($request->input('page_size', 10));
        }
        $data = $feedback->get();

        return $this->jsonData($data->count(), 'success', ['list' => $data, "total" => $total]);
    }

    public function del(Request $request)
    {
        // $feedback = feedback::where('id', $request->input('id'))->delete();
        return $this->jsonData(1, 'Ok');
    }
}
