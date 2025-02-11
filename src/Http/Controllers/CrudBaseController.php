<?php

namespace Harryes\CrudPackage\Http\Controllers;

use Harryes\CrudPackage\Helpers\ApiResponse;
use Harryes\CrudPackage\Helpers\MetaHelper;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Http\FormRequest;

class CrudBaseController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $model;
    protected $resource;

    /**
     * CrudBaseController constructor.
     * @param $model
     * @param $resource
     */
    public function __construct($model, $resource)
    {
        $this->model = $model;
        $this->resource = $resource;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $params = $request->all();
            $query = $this->model::initializeQuery();

            // Paginate results
            $perPage = $params['rowsPerPage'] ?? 0;
            $page = $params['page'] ?? 1;

            if($perPage == 0) {
                $items = $query->get();
                $meta = null;
            } else {
                $items = $query->paginate($perPage, ['*'], 'page', $page);

                $meta = MetaHelper::paginationMeta($items);
            }

            return ApiResponse::success($this->resource::collection($items), 'Records retrieved successfully.', 200, $meta);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve records.', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $storeRequestClass = "App\\Http\\Requests\\{$this->model}StoreRequest";
            if (class_exists($storeRequestClass)) {
                // Use the request class for validation
                $validatedData = app($storeRequestClass)->validated();
            } else {
                // Use inline validation rules from the controller
                $validatedData = $request->validate($this->storeValidationRules());
            }

            // Create the resource
            $item = $this->model::create($validatedData);

            // Call afterCreateProcess on the model
            if (method_exists($item, 'afterCreateProcess')) {
                $item->afterCreateProcess($request);
            }

            return ApiResponse::success(new $this->resource($item), 'Record created successfully.', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validation failed.', 422, $e->errors());
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create record.', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $item = $this->model::findOrFail($id);
            return ApiResponse::success(new $this->resource($item), 'Record retrieved successfully.');
        } catch (\Exception $e) {
            return ApiResponse::error('Record not found.', 404, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $updateRequestClass = "App\\Http\\Requests\\{$this->model}UpdateRequest";
            if (class_exists($updateRequestClass)) {
                // Use the request class for validation
                $validatedData = app($updateRequestClass)->validated();
            } else {
                // Use inline validation rules from the controller
                $validatedData = $request->validate($this->updateValidationRules());
            }

            // Update the resource
            $item = $this->model::findOrFail($id);
            $item->update($validatedData);

            // Call afterUpdateProcess on the model
            if (method_exists($item, 'afterUpdateProcess')) {
                $item->afterUpdateProcess($request);
            }

            return ApiResponse::success(new $this->resource($item), 'Record updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validation failed.', 422, $e->errors());
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update record.', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $item = $this->model::findOrFail($id);
            $item->delete();
            return ApiResponse::success(null, 'Record deleted successfully.');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete record.', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Validation rules for storing a new resource.
     *
     * @return array
     */
    protected function storeValidationRules(): array
    {
        return [];
    }

    /**
     * Validation rules for updating an existing resource.
     *
     * @return array
     */
    protected function updateValidationRules(): array
    {
        return [];
    }
}
