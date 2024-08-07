<?php

namespace Harry\CrudPackage\Helpers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as LaravelBaseController;

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

    public function index()
    {
        $items = $this->model::all();
        return $this->resource::collection($items);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->validationRules());
        $item = $this->model::create($data);
        return new $this->resource($item);
    }

    public function show($id)
    {
        $item = $this->model::findOrFail($id);
        return new $this->resource($item);
    }

    public function update(Request $request, $id)
    {
        $item = $this->model::findOrFail($id);
        $data = $request->validate($this->validationRules());
        $item->update($data);
        return new $this->resource($item);
    }

    public function destroy($id)
    {
        $item = $this->model::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }

    protected function validationRules()
    {
        // Define validation rules here or override in extended controllers
        return [];
    }
}
