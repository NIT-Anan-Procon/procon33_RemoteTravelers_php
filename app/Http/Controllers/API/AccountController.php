<?php

namespace App\Http\Controllers\API;

use App\Models\Account;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="API仕様書",
 *     description="",
 * )
 */

class AccountController extends Controller
{
    /**
     * @OA\POST(
     *      path="/api/user/signup,
     *      tags="User",
     *      summary="user_idを新規登録",
     *      description="user_idを新規登録し、取得するAPI",
     *      @OA\Response(
     *          response=200,
     *          description="正常な処理",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="ok",
     *                  type="boolean",
     *                  description="通信状況",
     *              ),
     *              @OA\Property(
     *                  property="data",
     *                  type="integer",
     *                  description="user_id",
     *              ),
     *              @OA\Property(
     *                  property="error",
     *                  type="String",
     *                  description="エラーメッセージ",
     *              ),
     *          )
     *      )
     *      @OA\Response(
     *          response=500,
     *          description="異常な処理",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="ok",
     *                  type="boolean",
     *                  description="通信状況",
     *              ),
     *              @OA\Property(
     *                  property="data",
     *                  type="integer",
     *                  description="user_id",
     *              ),
     *              @OA\Property(
     *                  property="error",
     *                  type="String",
     *                  description="エラーメッセージ",
     *              ),
     *          )
     *      )
     * )
     */
    public function signup() {
        try {
            $data = Account::insertGetId([
                'user_id' => null
            ]);

            $result = [
                'ok' => true,
                'data' => $data,
                'error' => null
            ];
            return $this->resConversionJson($result);
        } catch (Exception $e) {
            DB::rollBack();
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
