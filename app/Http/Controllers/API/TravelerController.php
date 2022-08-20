<?php

namespace App\Http\Controllers\API;

use App\Models\Travel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TravelerController extends Controller
{
    public function startTravel(Request $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->all();

            $host = $data['host'];
            $viewers = $data['viewers'];
            $travel_id = Travel::max('travel_id') + 1;

            // 旅行者をTravelsテーブルに追加
            Travel::insert([
                'travel_id' => $travel_id,
                'user_id' => $host,
                'traveler' => 1,
                'finished' => 0
            ]);
            // 閲覧者をTravelsテーブルに追加
            foreach ($viewers as $viewer) {
                Travel::insert([
                    'travel_id' => $travel_id,
                    'user_id' => $viewer,
                    'traveler' => 0,
                    'finished' => 0
                ]);
            }
            DB::commit();

            // レスポンスを返す
            $result = [
                'ok' => true,
                'data' => $travel_id,
                'error' => null,
            ];
            return $this->resConversionJson($result);
        } catch (\Exception $e) {
            DB::rollBack();

            // レスポンスを返す
            $result = [
                'ok' => false,
                'error' => $e->getMessage(),
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
