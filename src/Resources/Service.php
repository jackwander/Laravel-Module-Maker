<?php

namespace Jackwander\ModuleMaker\Resources;

use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client as GuzzleClient;

// base model for eloquent repository

class BaseService{

  protected $entity;

  public function __construct($entity)
  {
      $this->entity = $entity;
  }

  public function user() {
    return auth('api')->user();
  }

  public function filter($input, $entity = null){
    if (!$entity) {
      $entity = $this->entity;
    }
    $search = isset($input['search'])?$input['search']:null;
    $orderBy = isset($input['orderBy'])?$input['orderBy']:[];
    $data = $entity;
    $first = $entity->first();

    if(!$first){
      return $data;
    }
    $columns = $first->getFillable();

    foreach($orderBy as $key => $column){
      $data = $data->orderBy($key,$column);
    }

    if($search){
      $data = $data->where(function ($query) use ($search,$columns){
        foreach($columns as $column){
          $query->orWhere($column, 'like', $search.'%');
        }
      });
    }

    return $data;
  }

  public function paginateWithFilters($input){
    $search = isset($input['search'])?$input['search']:null;
    $page = isset($input['page'])?$input['page']:1;
    $size = isset($input['size'])?$input['size']:10;
    $orderBy = isset($input['orderBy'])?$input['orderBy']:[];
    $data = $this->filter($input)->paginate($size)->appends([
      'search' => $search,
      'page'=> $page,
      'size' => $size,
    ]);
    return $data;
  }

  public function create(array $data)
  {
    return $this->entity->create($data);
  }

  public function find($id)
  {
    return $this->entity->withoutGlobalScopes()->findOrFail($id);
  }

  public function findWithTrashed($id)
  {
    return $this->entity->whereId($id)->withTrashed()->first();
  }

  public function findBy($columns,$value)
  {
    if(is_array($columns)){
      $query = $this->entity;
      foreach($columns as $key => $column){
        $query = ($key == 0)?$query->where($column,$value):$query->orWhere($column,$value);
      }
      return $query->get();
    }

    return $this->entity->where($columns,$value)->get();
  }

  public function findFirstBy($column,$value){
    return $this->entity->where($column,$value)->first();
  }


  public function update(array $data, $identifier)
  {
    $entity = $this->entity->find($identifier);
    foreach ($entity->getFillable() as $key) {
      if (array_key_exists($key,$data)) {
        $array[$key]=$data[$key];
      }
    }

    if (empty($array)) {
      return false;
    }

    foreach($array as $key => $input){
      $entity->{$key} = $input;
    }
    return $entity->save();
  }

  public function all($request)
  {
    return $this->entity->all();
  }

  public function allWithTrashed() {
    return $this->entity->withTrashed()->get();
  }


  public function delete($id)
  {
    return $this->entity->withoutGlobalScopes()->find($id)->delete();
  }

  public function findBySlug($slug){
    return $this->entity->withoutGlobalScopes()->where('slug',$slug)->first();
  }

  public function forceDelete($id)
  {
    return $this->entity->withoutGlobalScopes()->find($id)->forceDelete();
  }

  public function firstOrCreate($data){
    return $this->entity->firstOrCreate($data);
  }

  public function model()
  {
    return $this->entity;
  }

  public function updateContent($data) {
    $data = $this->entity->findOrFail($data->id);
    $data->touch();
    return $data;
  }

  public function filterData($input,$data){
    $search = isset($input['search'])?$input['search']:null;
    $page = isset($input['page'])?$input['page']:1;
    $size = isset($input['size'])?$input['size']:10;
    $orderBy = isset($input['orderBy'])?$input['orderBy']:[];
    if ($data->count()==0) {
      return $data->paginate($size);
    }
    $first = $data->first();

    if(!$first){
      return $data;
    }
    $columns = array_keys($first->getAttributes());

    foreach($columns as $key => $value){
      if(in_array($value,['created_at','updated_at','deleted_at'])){
        unset($columns[$key]);
      }
    }

    foreach($orderBy as $key => $column){
      $data = $data->orderBy($key,$column);
    }

    if($search){
      $data = $data->where(function ($query) use ($search,$columns){
        foreach($columns as $column){
          $query->orWhere($column, 'like', '%'.$search.'%');
        }
      });
    }
    return $data->paginate($size);
  }

  public function convertToClassName($prefix, $string)
  {
    $name = '';
    $array = explode("_",$string);
    foreach ($array as $value) {
      $name = $name . ucfirst($value);
    }

    return "{$prefix}\\{$name}\\{$name}";
  } 
}
