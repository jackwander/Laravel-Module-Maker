<?php

namespace Jackwander\ModuleMaker\Base;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BaseApiController extends Controller
{
    protected $repository;
    protected $module;
    protected $resourceClass;

    public function __construct($repository, $module, $resourceClass = null)
    {
        $this->repository = $repository;
        $this->module = $module;
        $this->resourceClass = $resourceClass;
    }

    /**
     * Helper function to wrap data in a Laravel API Resource if one is defined.
     */
    protected function toResource($data, $isCollection = false)
    {
        if (!$this->resourceClass || !class_exists($this->resourceClass)) {
            return $data;
        }

        return $isCollection 
            ? $this->resourceClass::collection($data) 
            : new $this->resourceClass($data);
    }

    public function index(Request $request)
    {
        $data = $this->repository->paginateWithFilters($request->all());
        
        return response()->json(
            $this->resourceClass ? $this->toResource($data, true) : $data,
            200
        );
    }

    public function all(Request $request)
    {
        $data = $this->repository->all($request->all());
        
        return response()->json(
            $this->resourceClass ? $this->toResource($data, true) : $data,
            200
        );
    }

    public function store(Request $request)
    {
        $model = $this->repository->create($request->all());

        return response()->json([
            'message' => $this->module . ' Successfully Created',
            'data'    => $this->resourceClass ? $this->toResource($model) : $model->toArray()
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $this->repository->update($request->all(), $id);
        $fetchedModel = $this->repository->find($id);

        return response()->json([
            'message' => $this->module . ' Successfully Updated',
            'data'    => $this->resourceClass ? $this->toResource($fetchedModel) : $fetchedModel
        ], 200);
    }

    public function destroy($id)
    {
        $item = $this->repository->find($id);
        $this->repository->delete($id);

        return response()->json([
            'message' => $this->module . ' Successfully Deleted',
            'data'    => $item
        ], 200);
    }

    public function show($id)
    {
        $data = $this->repository->find($id);
        
        return response()->json(
            $this->resourceClass ? $this->toResource($data) : $data,
            200
        );
    }

    public function relation($id, $relation)
    {
        return response()->json($this->repository->relation($id, $relation)->toArray(), 200);
    }

    public function findBySlug($slug)
    {
        $data = $this->repository->findBySlug($slug);

        return response()->json(
            $this->resourceClass ? $this->toResource($data) : $data,
            200
        );
    }

    public function guard()
    {
        return Auth::guard('api');
    }

    public function updateContent(Request $request)
    {
        $item = $this->repository->updateContent($request);

        return response()->json([
            'message' => $this->module . ' Successfully Updated the Content',
            'data'    => $item
        ], 200);
    }

    public function addFile(Request $request)
    {
        $item = $this->repository->addFile($request);

        return response()->json([
            'message' => $this->module . ' Successfully Added the File',
            'data'    => $item
        ], 200);
    }

    public function deleteFile($id)
    {
        $item = $this->repository->deleteFile($id);

        return response()->json([
            'message' => $this->module . ' Successfully Deleted the File',
            'data'    => $item
        ], 200);
    }

    public function requestValidator(Request $request, $data)
    {
        return Validator::make($request->all(), $data);
    }
}
