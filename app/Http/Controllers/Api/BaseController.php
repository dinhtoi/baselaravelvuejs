<?php
/**
 * File BaseController.php
 *
 * @author Tuan Duong <bacduong@gmail.com>
 * @package Laravue
 * @version 1.0
 */
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class BaseController
 *
 * @package App\Http\Controllers\Api
 */
class BaseController extends Controller
{
    protected $request;
    protected $service;

    public function __construct()
    {
        $this->request = request();
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        return $this->indexDefault($request);
    }

    public function indexDefault(Request $request, array $relations = [], $withTrashed = false)
    {
        try {
            $request = $request ?: $this->request;
            $params = $request->only(['page', 'limit', 'filter', 'sort']);
            $data = $this->service->buildBasicQuery($params, $relations, $withTrashed);
            return $this->successResponse($data);
        } catch (Exception $e) {
            return $this->errorResponse();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id)
    {
        return $this->showDefault($id);
    }

    public function showDefault($id, array $relations = [], array $appends = [], array $hiddens = [], $withTrashed = false)
    {
        try {
            $data = $this->service->show($id, $relations, $appends, $hiddens, $withTrashed);
            return $this->successResponse($data);
        } catch (Exception $e) {
            return $this->errorResponse();
        }
    }
}
