<?php

namespace App\Http\Controllers;

use App\Category;
use Illuminate\Http\Request;
use App\Post;


class PruebasController extends Controller
{

    public function index(){
    }

    public function testOrm(){
        /*
          $posts= Post::all();

        foreach ($posts as $post){
            echo "<h1>".$post->title."</h1>";
            echo "<spam style='color:gray'>".$post->user->name. " - " .$post->category->name."</spam>";
            echo "<p>".$post->content."</p>";
            echo "<hr>";
        }

        */

        $categories= Category::all();

        foreach ($categories as $category){
            echo "<h1>".$category->name."</h1>";

            foreach ($category->posts as $post){
                echo "<h3>".$post->title."</h3>";
                echo "<spam style='color:gray'>".$post->user->name. " - " .$post->category->name."</spam>";
                echo "<p>".$post->content."</p>";

            }

            echo "<hr>";

        }

        die();
    }
}
