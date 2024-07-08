<?php

namespace Jackwander\ModuleMaker\Resources;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Middleware\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BaseApiController extends Controller
{
  protected $repository, $module;
  public function __construct($repository, $module){
    $this->repository = $repository;
    $this->module = $module;
  }

  public function index(Request $request){
    return response()->json($this->repository->paginateWithFilters($request->all()),200);
  }

  public function all(Request $request){
    return response()->json($this->repository->all($request->all()),200);
  }

  public function store(Request $request){
    $repository = $this->repository->create($request->all());
    return response()->json([
      'message' => $this->module.' Successfully Created',
      'data' => $repository->toArray()
    ],200);
  }


  public function update(Request $request,$id){
    $repository = $this->repository->update($request->all(),$id);
    return response()->json([
      'message' => $this->module.' Successfully Updated',
      'data' => $this->repository->find($id)
    ],200);
  }

  public function destroy($id){
    $item = $this->repository->find($id);
    $this->repository->delete($id);
    return response()->json([
      'message' => $this->module.' Successfully Deleted',
      'data' => $item
    ],200);
  }

  public function show($id){
    return response()->json($this->repository->find($id),200);
  }

  public function relation($id,$relation){
    return response()->json($this->repository->relation($id,$relation)->toArray(),200);
  }

  public function findBySlug($slug) {
    return response()->json($this->repository->findBySlug($slug),200);
  }

  public function guard()
  {
    return Auth::guard('api');
  }

  public function updateContent(Request $request) {
    $item = $this->repository->updateContent($request);
    return response()->json([
      'message' => $this->module.' Successfully Updated the Content',
      'data' => $item
    ],200);
  }

  public function addFile(Request $request) {
    $item = $this->repository->addFile($request);
    return response()->json([
      'message' => $this->module.' Successfully Added the File',
      'data' => $item
    ],200);
  }

  public function deleteFile($id) {
    $item = $this->repository->deleteFile($id);
    return response()->json([
      'message' => $this->module.' Successfully Deleted the File',
      'data' => $item
    ],200);
  }

  public function requestValidator($request, $data) {
    return $validator = Validator::make($request->all(), $data);
  }

}
