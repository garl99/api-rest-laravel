<?php

namespace App\Http\Controllers;

use App\Helpers\JwtAuth;
use App\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    public function __construct(){
        $this->middleware('api.auth',['except' => ['index','show','getImage',
                                                             'getPostsByCategory','getPostsByUser']]);
    }

    public function index(){
        $posts = Post::all()->load('category');

        
        if(!empty($posts)){
            $data = array(
                'status'    => 'success',
                'code'      => 200,
                'posts'      => $posts
            );
        }
        else{
            $data = array(
                'status'    => 'error',
                'code'      => 400,
                'message'   => 'No existe ningun post'
            );
        }

        

        return response()->json($data,$data['code']);

    }

    public function show($id){

        $post = Post::find($id)->load('category')
                               ->load('user');

        if(is_object($post)){
            $data = array(
                'status'    => 'success',
                'code'      => 200,
                'post'      => $post
            );
        }
        else{
            $data = array(
                'status'    => 'error',
                'code'      => 400,
                'message'   => 'No existe ese post'
            );

        }

        return response()->json($data,$data['code']);

    }

    public function store(Request $request){

        //Recoger datos por POST
        $json = $request->input('json',null);
        $params = json_decode($json,null);
        $params_array=json_decode($json,true);

        if(!empty($params_array)) {

            //Conseguir usuario identificado
            $user = $this->getIdentity($request);


            //Validar datos
            $validate = Validator::make($params_array,[
                'title'         =>  'required',
                'content'       =>  'required',
                'category_id'   =>  'required',
                'image'         =>  'required'
            ]);


            if($validate->fails()){
                $data = array(
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'No se ha guardado el post. Faltan datos'
                );

            }else{
                //Guardar post
                $post = new Post();
                $post->user_id = $user->sub;
                $post->category_id = $params->category_id;
                $post->title = $params->title;
                $post->content = $params->content;
                $post->image = $params->image;

                $post->save();


                $data = array(
                    'status'    => 'success',
                    'code'      => 200,
                    'post'      => $post
                );

            }
        }
        else{
            $data = array(
                'status'    => 'error',
                'code'      => 400,
                'message'   => 'No se ha enviado ningun dato'
            );
        }

        //Devolver respuesta

        return response()->json($data,$data['code']);

    }

    public function update($id,Request $request){
        //Recoger los datos por POST
        $json = $request->input('json',null);
        $params = json_decode($json,null);
        $params_array = json_decode($json,true);

        $data = array(
            'status'    => 'error',
            'code'      => 400,
            'message'   => 'No se logro actualizar el post'
        );

        if(!empty($params_array)) {

            //Validar los datos
            $validate = Validator::make($params_array, [
                'title'         => 'required',
                'content'       => 'required',
                'category_id'   => 'required'
            ]);

            if($validate->fails()){
                $data['erros'] = $validate->errors() ;
                return response()->json($data,$data['code']);
            }

            //Eliminar lo que no queremos actualizar
            unset($params_array['id']);
            unset($params_array['user_id']);
            unset($params_array['created_at']);
            unset($params_array['user']);

            //Conseguir usuario identificado
            $user = $this->getIdentity($request);

            //Conseguir el registro
            $post = Post::where('id',$id)
                        ->where('user_id',$user->sub)
                        ->first();

            if(!empty($post) && is_object($post)){
                //Actualizar post
                $post->update($params_array);



                //Devolver respuesta
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'post' => $post,
                    'changes' => $params_array
                );

            }

            /*
            $where = [
                'id'        =>  $id,
                'user_id'   =>  $user->sub
            ];

            $post = Post::updateOrCreate($where,$params_array);
            */

        }


        return response()->json($data,$data['code']);
    }

    public function destroy($id,Request $request){
        //Conseguir usuario identificado
        $user = $this->getIdentity($request);

        //Conseguir el registro
        $post = Post::where('id',$id)
                    ->where('user_id',$user->sub)
                    ->first();

        if(!empty($post)) {

            //Borrar el registro
            $post->delete();

            //Devolver respuesta

            $data = array(
                'status' => 'success',
                'code' => 200,
                'post' => $post
            );
        }
        else{
            $data = array(
                'status'    => 'error',
                'code'      => 404,
                'message'   => 'El post no existe'
            );
        }

        return response()->json($data,$data['code']);

    }

    private function getIdentity($request){
        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization',null);
        $user = $jwtAuth->checkToken($token, true);

        return $user;
    }

    public function upload(Request $request){
        //Recoger la imagen de la petición
        $image = $request->file('file0');

        //Validar la imagen

        $validate = Validator::make($request->all(),[
            'file0' => 'required|image|mimes:png,jpg,jpeg,gif'
        ]);

        //Guardar imagen en disco

        if(!$image && $validate->fails()){
            $data = array(
                'status'    => 'error',
                'code'      => 404,
                'message'   => 'Error al subir la imagen'
            );
        }
        else{
            $image_name = time().$image->getClientOriginalName();
            Storage::disk('images')->put($image_name, \File::get($image));


            $data = array(
                'status' => 'success',
                'code' => 200,
                'image' => $image_name
            );

        }

        //Devolver datos

        return response()->json($data,$data['code']);

    }

    public function getImage($filename){
        //Comprobar si existe el fichero
        $isset = Storage::disk('images')->exists($filename);


        if($isset){
            //Conseguir la imagen
            $file = Storage::disk('images')->get($filename);

            //Devolver imagen
            return new Response($file);
        }

        else{
            $data = array(
                'status'    => 'error',
                'code'      => 404,
                'message'   => 'La imagen no existe'
            );
        }


        //Devolver error
        return response()->json($data,$data['code']);

    }

    public function getPostsByCategory($id){
        $posts = Post::where('category_id',$id)->get();

        $data = array(
            'status' => 'success',
            'code' => 200,
            'posts' => $posts
        );

        return response()->json($data,$data['code']);

    }

    public function getPostsByUser($id){
        $posts = Post::where('user_id',$id)->get();

        $data = array(
            'status' => 'success',
            'code' => 200,
            'posts' => $posts
        );

        return response()->json($data,$data['code']);


    }
}

