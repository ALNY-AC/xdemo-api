<?php

namespace  App\Http\Controllers\H5; // @todo: 这里是要生成类的命名空间

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{

	// 地址列表
	public function list(Request $request)
	{

		$DB = DB::table('address')
			->where('data_state', 1)
			->where('user_id', $request->get('jwt')->id)
			->orderBy('add_time', 'desc');

		$result = $DB->get();


		return [
			'code' => $result ? 1 : -1,
			'msg' => $result ? 'success' : 'error',
			'data' => $result,
		];
	}

	// 地址详情
	public function info(Request $request)
	{

		$result = DB::table('address')
			->where('data_state', 1)
			->where('id', $request->input('id'))
			->first();


		return [
			'code' => $result ? 1 : -1,
			'msg' => $result ? 'success' : 'error',
			'data' => $result,
		];
	}

	// 保存或者新增
	public function save(Request $request)
	{

		if ($request->input('is_default') == 1) {
			DB::table('address')->update(['is_default' => 0]);
		}

		if ($request->filled('id')) {

			$result = DB::table('address')
				->where('id', $request->input('id'))
				->update($request->all());

			return response()->json([
				'code' => $result >= 0 ? 1 : -1,
				'msg' =>  $result >= 0 ? 'success' : 'error',
				'data' => $result,
			]);
		} else {

			$data = $request->toArray();

			$data['user_id'] = $request->get('jwt')->id;

			$result = DB::table('address')->insert($data);

			return [
				'code' => $result ? 1 : -1,
				'msg' => $result ? 'success' : 'error',
				'data' => $result,
			];
		}
	}

	// 删除接口
	public function del(Request $request)
	{

		$result = DB::table('address')
			->where('id', $request->input('id'))
			->delete();

		return [
			'code' => $result ? 1 : -1,
			'msg' => $result ? 'success' : 'error',
			'data' => $result,
		];
	}
}
