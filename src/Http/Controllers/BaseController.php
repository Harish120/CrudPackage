<?php

namespace Harryes\CrudPackage\Http\Controllers;

use Harryes\CrudPackage\Helpers\ApiResponse;
use Harryes\CrudPackage\Helpers\MetaHelper;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as LaravelBaseController;
use Illuminate\Support\Facades\Storage;

class BaseController extends LaravelBaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $model;
    protected $resource;

    public function __construct($model, $resource)
    {
        $this->model = $model;
        $this->resource = $resource;
    }

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

    public function store(Request $request)
    {
        try {
            $data = $request->validate($this->storeValidationRules());
            $item = $this->model::create($data);
            return ApiResponse::success(new $this->resource($item), 'Record created successfully.', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validation failed.', 422, $e->errors());
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create record.', 500, ['error' => $e->getMessage()]);
        }
    }

    public function show($id)
    {
        try {
            $item = $this->model::findOrFail($id);
            return ApiResponse::success(new $this->resource($item), 'Record retrieved successfully.');
        } catch (\Exception $e) {
            return ApiResponse::error('Record not found.', 404, ['error' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $item = $this->model::findOrFail($id);
            $data = $request->validate($this->updateValidationRules());
            $item->update($data);
            return ApiResponse::success(new $this->resource($item), 'Record updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validation failed.', 422, $e->errors());
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update record.', 500, ['error' => $e->getMessage()]);
        }
    }

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

    protected function storeValidationRules(): array
    {
        // This should be overridden in specific controllers if needed
        return [];
    }

    protected function updateValidationRules(): array
    {
        // This should be overridden in specific controllers if needed
        return [];
    }
}
