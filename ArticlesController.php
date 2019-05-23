<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Articles;
use App\Http\Requests\ArticlesPostRequest;
use App\Helpers\Translit;

class ArticlesController extends Controller
{
    public function __construct()
    {
    	\App::setLocale('ru');
    }

    public function getIndex()
    {
        return view('admin.articles', [
    		'data' => Articles::orderBy('created_at', 'desc')->paginate(25)
    	]);
    }

    public function getForm($id = 0)
    {
    	return view('admin.articles_form', [
    		'data' => Articles::find($id)
    	]);
    }

    public function getSave(ArticlesPostRequest $request, $id = 0)
    {
    	$articles = Articles::findOrNew($id);
    	$articles->title = $request->input('title');
        $articles->description = $request->input('description');
        $articles->text = $request->input('text');
        $articles->url = Translit::slug($request->input('title'));
        $articles->meta_title = $request->input('meta_title');
        $articles->meta_keywords = $request->input('meta_keywords');
        $articles->meta_description = $request->input('meta_description');

        if($request->hasFile('images'))
    	{
            $patch = 'uploads/articles';

            @unlink('./' . $patch . '/' . $articles->img);

            $v_img = $request->file('images')[0];
            
            $img_ext = $v_img->extension();
            $name_img = str_random(8) . '.' . $img_ext;
            $v_img->move($patch, $name_img);
            
            $articles->img = $name_img;
    	}

        $articles->save();

    	return response()->json(['success' => true, 'href' => '/admin/articles/form/' . $articles->id]);
    }

    public function postDelete($id = 0)
    {
        $articles = Articles::find($id);

        if(is_object($articles))
        {
            $articles->delete();
            return response()->json(['success' => true, 'href' => '/admin/articles']);
        }
        else
        {
            return response()->json(['success' => false, 'href' => '/admin/articles']);
        }
    }
}
