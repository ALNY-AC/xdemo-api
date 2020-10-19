<?php


namespace App\Http\Response;

/**
 * Trait ResponseJson
 * @package App\Http\Response
 */
trait ResponseJson
{
    /**
     * 接口出现业务异常返回
     * @param $code
     * @param $msg
     * @param array $data
     * @return false|string
     */
    public function jsonData($code, $msg, $data = [])
    {
        return $this->jsonResponse($code, $msg, $data);
    }

    /**
     * 接口成功返回
     * @param array $data
     * @return false|string
     */
    public function jsonSuccessData($data = [])
    {
        return $this->jsonResponse(0, 'OK', $data);
    }

    /**
     * 返回一个json
     * @param $code
     * @param $msg
     * @param $data
     * @return false|string
     */
    public function jsonResponse($code, $msg, $data)
    {
        $content = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ];

        return response()->json($content);
    }
}