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
    protected $storeRequest;
    protected $updateRequest;

    /**
     * CrudBaseController constructor.
     * @param $model
     * @param $resource
     * @param $storeRequest
     * @param $updateRequest
     */
    public function __construct($model, $resource, $storeRequest = null, $updateRequest = null)
    {
        $this->model = $model;
        $this->resource = $resource;
        $this->storeRequest = $storeRequest;
        $this->updateRequest = $updateRequest;
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

            if ($perPage == 0) {
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
            $validatedData = $this->validateRequest($request, $this->storeRequest, 'storeValidationRules');

            // Handle file uploads dynamically
            foreach ($request->allFiles() as $key => $file) {
                $validatedData[$key] = $file->store('uploads');
            }

            // Create the resource
            $item = $this->model::create($validatedData);

            // Call afterCreateCallback on the model if it exists
            if (method_exists($item, 'afterCreateCallback')) {
                $item->afterCreateCallback($request->all());
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
    public function update(Request $request, int $id)
    {
        try {
            $validatedData = $this->validateRequest($request, $this->updateRequest, 'updateValidationRules');

            // Handle file uploads dynamically
            foreach ($request->allFiles() as $key => $file) {
                $validatedData[$key] = $file->store('uploads');
            }

            // Find and update the resource
            $item = $this->model::findOrFail($id);
            $item->update($validatedData);

            // Call afterUpdateCallback on the model if it exists
            if (method_exists($item, 'afterUpdateCallback')) {
                $item->afterUpdateCallback($request->all());
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
     * Validate the request using the injected custom request class or default method.
     *
     * @param Request $request
     * @param string|null $customRequest
     * @param string $validationMethod
     * @return array
     */
    protected function validateRequest(Request $request, ?string $customRequest, string $validationMethod): array
    {
        if ($customRequest && class_exists($customRequest)) {
            $validatedRequest = app($customRequest);
            if ($validatedRequest instanceof FormRequest) {
                return $validatedRequest->validated();
            }
        }

        return $request->validate($this->$validationMethod());
    }
}