<?php

namespace App\Traits;


trait HttpResponses
{
    protected function success($data, $code)
    {
        return response()->json([
            'status' => 'success',
            'status_code' => $code,
            'data' => $data
        ], $code);
    }

    protected function error($data, $code){
        return response()->json([
            'status' => 'error',
            'status_code' => $code,
            'data' => $data
        ], $code);
    }
}
