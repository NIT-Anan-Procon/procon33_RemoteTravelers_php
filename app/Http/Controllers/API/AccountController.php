<?php

namespace App\Http\Controllers\API;

use App\Models\Account;
use App\Http\Controllers\Controller;

class AccountController extends Controller
{

    public function signup() {
        try {
            // ユーザを新規登録し、登録したユーザのIDを取得
            $data = Account::insertGetId([]);

            // レスポンスを返す
            $result = [
                'ok' => true,
                'data' => $data,
                'error' => null
            ];
            return $this->resConversionJson($result);
        } catch (\Exception $e) {
            // レスポンスを返す
            $result = [
                'ok' => false,
                'data' => null,
                'error' => $e->getMessage()
            ];
            return $this->resConversionJson($result, $e->getCode());
        }
    }

    private function resConversionJson($result, $statusCode=200)
    {
        if(empty($statusCode) || $statusCode < 100 || $statusCode >= 600){
            $statusCode = 500;
        }
        return response()->json($result, $statusCode, ['Content-Type' => 'application/json'], JSON_UNESCAPED_SLASHES);
    }
}
