<?php

namespace App\Http\Controllers;

use App\Category;
use http\Params;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{

    public function __construct(){
       $this->middleware('api.auth',['except' => ['index','show']]);
    }

    public function index(){
        $categories = Category::all();

        $data = array(
            'status'            => 'success',
            'code'              => 200,
            'categories'        => $categories
        );

        return response()->json($data,$data['code']);
    }

    public function show($id){
        $category = Category::find($id);

        if(is_object($category)){
            $data = array(
                'status'            => 'success',
                'code'              => 200,
                'category'        => $category
            );
        }
        else{
            $data = array(
                'status'    => 'error',
                'code'      => 400,
                'message'   => 'La categoria no existe'
            );

        }

        return response()->json($data,$data['code']);
    }


    public function store(Request $request){

        //Recoger los datos por POST

        $json = $request->input('json',null);
        $params = json_decode($json,null);
        $params_array= json_decode($json,true);


        if(!empty($params_array)) {
            //Validar los datos
            $validate = Validator::make($params_array, [
                'name' => 'required|unique:categories'
            ]);


            if ($validate->fails()) {
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'No se ha guardado la categoria'
                );
            } else {
                //Guardar la categoria

                $category = new Category();
                $category->name = $params->name;
                $category->save();


                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'category' => $category
                );

            }

        }
        else{
            $data = array(
                'status' => 'error',
                'code' => 400,
                'message' => 'No se envio ninguna categoria'
            );
        }


        //Devolver los resultados

        return response()->json($data,$data['code']);

    }


    public function update($id,Request $request){
        //Recoger los datos que llegar por POST}
        $json = $request->input('json',null);
        $params= json_decode($json,null);
        $params_array=json_decode($json,true);

        if(!empty($params_array)) {
            //Validar los datos

            $validate = Validator::make($params_array, [
                'name'  =>  'required|unique:categories,'.$id

            ]);
            //Quitar lo que no quiero actualizar
            unset($params_array['id']);
            unset($params_array['created_at']);

            //Actualizar registro de categoria

            $category= Category::where('id',$id)->update($params_array);

            //Devolver respuesta

            $data = array(
                'status' => 'success',
                'code' => 200,
                'category' => $params_array
            );
        }
        else{
            $data = array(
                'status' => 'error',
                'code' => 400,
                'message' => 'No se envio ninguna categoria para actualizar'
            );

        }

        return response()->json($data,$data['code']);
    }

}
