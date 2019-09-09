<?php

namespace App\Http\Controllers;

use App\Helpers\JwtAuth;
use App\User;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function pruebas(Request $request){
        echo "Acción de prueba de USER-CONTROLLER";
    }

    //Registro de usuario
    public function register(Request $request){



        //Recoger los datos del usuario
        $json= $request->input('json',null);
        $params= json_decode($json);
        $params_array= json_decode($json,true);

        if (!empty($params_array) && !empty($params)) {

                //Limpiar datos
                $params_array = array_map('trim', $params_array);

                //Validar datos
                $validate = Validator::make($params_array, [
                        'name'      => 'required|alpha',
                        'surname'   => 'required|alpha',
                        'email'     => 'required|email|unique:users',  //Unique -> Comprobar si el usuario ya existe
                        'password'  => 'required'
                    ]
                );

                if ($validate->fails()) {
                    // La validación ha fallado

                    $data = array(
                        'status'    => 'error',
                        'code'      => 404,
                        'message'   => 'El usuario no se creado correctamente',
                        'errors'    => $validate->errors()
                    );
                } else {
                    // Validación pasada correctamente

                    //Cifrar contraseña

                    //$pwd= password_hash($params->password,PASSWORD_BCRYPT,['cost' => 4]);

                    $pwd = hash('sha256',$params->password);

                    //Crear el usuario

                    $user= new User();
                    $user->name= $params_array['name'];
                    $user->surname=$params_array['surname'];
                    $user->email=$params_array['email'];
                    $user->role='ROLE_USER';
                    $user->password=$pwd;

                    //Guardar usuario
                    $user->save();

                    $data = array(
                        'status'    => 'success',
                        'code'      => 200,
                        'message'   => 'El usuario se ha creado correctamente',
                        'user'      => $user
                    );
                }
        }
        else{
            $data = array(
                'status'    => 'error',
                'code'      => 400,
                'message'   => 'Los datos suministrados no son correctos'
            );
        }

        return response()->json($data);


    }


    //Login de usuario
    public function login (Request $request){
        $jwtAuth= new JwtAuth();


        //Recibir datos por POST
        $json= $request->input('json',null);
        $params= json_decode($json);
        $params_array=json_decode($json,true);

        //Validar datos
        $validate = Validator::make($params_array, [
                'email'     => 'required|email',
                'password'  => 'required'
            ]
        );

        if ($validate->fails()) {
            // La validación ha fallado

            $signup = array(
                'status'    => 'error',
                'code'      => 404,
                'message'   => 'El usuario no se ha podido identitifcar',
                'errors'    => $validate->errors()
            );
        } else {
            //Cifrar la contraseña
            $pwd= hash('sha256',$params->password);

            //Devolver token o datos
            $signup= $jwtAuth->signup($params->email,$pwd);

            if(!empty($params->getToken)){
                $signup= $jwtAuth->signup($params->email,$pwd,true);
            }
        }


        return response()->json($signup,200);

    }

    public function update(Request $request){
        //Comprobar si el usuario esta identificado
        $token = $request->header('Authorization');
        $jwtAuth = new JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);

        //Recoger los datos por POST

        $json=$request->input('json',null);
        $params_array=json_decode($json,true);

        if($checkToken && !empty($params_array)){
            //Actualizar usuario

            //Sacar usuario identificado

            $user = $jwtAuth->checkToken($token,true);

            //Validar datos
            $validate=Validator::make($params_array,[
                'name'      => 'required|alpha',
                'surname'   => 'required|alpha',
                'email'     => 'required|email|unique:users,'.$user->sub
            ]);

            //Quitar campos que no quiero actualizar

            unset($params_array['id']);
            unset($params_array['role']);
            unset($params_array['password']);
            unset($params_array['created_at']);
            unset($params_array['remember_token']);

            //Actualizar usuario en la base de datos

            $user_update=User::where('id',$user->sub)->update($params_array);

            //Devolver array con resultado*/

            $data = array(
                'status'    => 'success',
                'code'      => 200,
                'user'      => $user,
                'changes'   => $params_array
            );
        }
        else{
            $data = array(
                'status'    => 'error',
                'code'      => 400,
                'message'   => 'El usuario no esta identificado'
            );
        }

        return response()->json($data,$data['code']);

    }

    public function upload(Request $request){

        //Recoger datoss del peticion

        $image=$request->file('file0');

        //Validacion de la imagen

        $validate = Validator::make($request->all(),[
           'file0'  =>  'required|image|mimes:jpg,jpeg,png,gif'
        ]);

        //Guardar imagen

        if(!$image || $validate->fails()){
            $data = array(
                'status'    => 'error',
                'code'      => 400,
                'message'   => 'Error al subir la foto'
            );
        }
        else{
            $image_name=time().$image->getClientOriginalName();
            \Storage::disk('users')->put($image_name,\File::get($image));

            $data = array(
                'status'    => 'success',
                'code'      => 200,
                'image'     => $image_name
            );

        }

        return response()->json($data,$data['code']);

    }


    public function getImage($filename){

        $isset=Storage::disk('users')->exists($filename);

        if($isset){
            $file = Storage::disk('users')->get($filename);
            return new Response($file,200);
        }
        else{
            $data = array(
                'status'    =>  'error',
                'code'      =>  400,
                'message'   =>  'La imagen no existe.'
            );

            return response()->json($data,$data['code']);

        }

    }


    public function detail($id){

        $user= User::find($id);

        if(is_object($user)){

            $data = array(
                'status'    => 'success',
                'code'      => 200,
                'user'      => $user
            );

        }
        else{
            $data = array(
                'status'    =>  'error',
                'code'      =>  400,
                'message'   =>  'El usuario no existe.'
            );

        }

        return response()->json($data,$data['code']);
    }
}
