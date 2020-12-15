<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    /**
     * @param array $responseData
     * @return JsonResponse
     */
    protected function successResponse($responseData = ['message' => 'OK'])
    {
        return response()->json($responseData);
    }

    /**
     * @param array $errorData
     * @return JsonResponse
     */
    protected function errorResponse($errorData = null)
    {
        $errorData = $errorData ? ['errors' => $errorData] : ['errors' => __('messages.common.server_error')];
        return response()->json($errorData, isset($errorData['code']) ? $errorData['code'] : Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
