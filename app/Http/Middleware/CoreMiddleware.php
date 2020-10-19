<?php

namespace App\Http\Middleware;

use App\Http\Response\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CoreMiddleware
{

    use ResponseJson;
    public function handle(Request $request, \Closure $next)
    {

        $jwt = $request->header('Authorization');
        if (!$jwt || $jwt === 'undefined') {
            return $this->jsonData(401, 'ERR authorization');
        }
        // $jwt = "eyJpdiI6InlRMiticE9PTWFsWTA5eVhic21Yd2c9PSIsInZhbHVlIjoiZHZEdTJ3STZKL2RLWDYzM1p3anJtZVRvVXplUzJHOXhGZlExU1NaYURmdU45NDZYemhpc044TzBQZGhzdE4yQVZ6WmxiQjZueVBRSWJQNlpoZlZzKzBBbkcvNUtxZHQ5M0dNaUhYTnpDMStZd29LRlp1bm9NQmgrZktoellaSEI2dzIwa2t6dUtWckU4bXhnM1Bjdmt1QTk5clF3dGN1dVhJWE56bEQrQ01NZWh1VWhFdk5FdUUxOWpiUDAwZ0U0bWlWWUxvd3hBVmRxY3RiRUxZQVhraXNtalQyU0kxOUl1WFNPT1lqbzhNcm91aUZVeHMrbFYydzJ0QzFjV0ZCZUZWQmo2UjBHaXo5T2VhQ053VVZrWUE9PSIsIm1hYyI6IjVmMTI5YTExMWUwOGU4ZDg0YmFjOTk0YzQwMDNmZDkwNDY1MzFmNDViZDgwMzM5ZWJiM2Y4N2M1ZmZkOGU4YzgifQ==";
        $jwt = json_decode(decrypt((string) $jwt));
        $request->attributes->add(['jwt' => $jwt]);
        return $next($request);
    }
}
