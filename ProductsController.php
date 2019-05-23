<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Products;
use App\Models\ProductsGroup;
use App\Models\Manufacturers;
use App\Models\ProductsImages;
use App\Models\Options;
use App\Models\ProductsOptions;
use App\Models\Likewises;
use App\Http\Requests\ProductsPostRequest;
use App\Models\CharacteristicsValue;
use App\Models\ProductsCharacteristics;
use Image;
use App\Helpers\Translit;
use App\Models\Tag;

class ProductsController extends Controller
{
	public function __construct()
	{
		\App::setLocale('ru');
	}

	public function getIndex($group_id)
	{
		return view('admin.products', [
			'data' => Products::all(),
			'groups' => ProductsGroup::all(),
			'group_id' => $group_id,
			'breadcrumb' => ProductsGroup::breadcrumb($group_id),
		]);
	}

	public function getForm($group_id, $id = 0)
	{
		$prod = Products::with('options')->find($id);

		return view('admin.products_form', [
			'data' => $prod,
			'group_id' => $group_id,
			'breadcrumb' => ProductsGroup::breadcrumb($group_id),
			'manufacturer' => Manufacturers::orderBy('title')->pluck('title', 'id')->toArray(),
			'option' => Options::where('subid', 0)->get(),
			'prodCharacteristics' => ProductsCharacteristics::where('subid', 0)->pluck('name', 'id')->toArray()
		]);
	}

	public function getSave(ProductsPostRequest $request, $group_id, $id = 0)
	{
		$tags = $request->input('tags');
		$manufacturers = Manufacturers::find($request->input('manufacturers_id', 0));

		if(is_object($manufacturers))
		{
			$url = object_get($manufacturers, 'title') . '-' . $request->input('title');
		}
		else
		{
			$url = $request->input('title');
		}

		$products = Products::findOrNew($id);
		$products->group_id = $group_id;
		$products->manufacturers_id = $request->input('manufacturers_id', 0);
		
		if($request->input('characteristics_id', 0) > 0) 
		{
			$products->characteristics_id = $request->input('characteristics_id', 0);
		}

		$products->title = $request->input('title');
		$products->cost = $request->input('cost', 0);
		$products->description = $request->input('description');
		
		if(!empty($request->input('url')))
		{
			$products->url = $request->input('url');
		}
		else
		{
			$products->url = Translit::slug($url);
		}
		
		$products->stock = $request->input('stock', 0);
		$products->video = $request->input('video', 0);
		$products->tags = $tags;
		$products->save();

		Tag::parsePost($tags, $products->id);
		
		ProductsOptions::where('prod_id', $products->id)->delete();
		
		if($request->has('option'))
		{
			foreach($request->input('option') as $key => $val)
			{
				if(!empty($val['chk']))
				{
					$options = new ProductsOptions;
					$options->option_id = $key;
					$options->option_subid = array_get($val, 'subid');
					$options->prod_id = $products->id;
					$options->cost = array_get($val, 'cost');
					$options->basket = array_get($val, 'basket', 0);
					$options->save();
				}
			}
		}

		// $post_characteristics_value = $request->input('characteristics_value');

		// if(!empty($post_characteristics_value) && is_array($post_characteristics_value))
		// {
		// 	foreach($post_characteristics_value as $id_characteristics_value => $val_characteristics_data) 
		// 	{
		// 		$characteristicsValue = CharacteristicsValue::where('prod_id', $products->id)
		// 			->where('characteristics_id', $id_characteristics_value)
		// 			->first();

		// 		if(empty($characteristicsValue))
		// 		{
		// 			$characteristicsValue = new CharacteristicsValue;
		// 		}

		// 		$characteristicsValue->characteristics_id = $id_characteristics_value;
		// 		$characteristicsValue->prod_id = $products->id;
		// 		$characteristicsValue->name = $val_characteristics_data;
		// 		$characteristicsValue->save();
		// 	}
		// }

		return response()->json(['success' => true, 'href' => '/admin/products/list/form/' . $group_id . '/' . $products->id]);
	}

	public function postDelete($group_id, $id = 0)
	{
		$products = Products::find($id);

		if(is_object($products))
		{
			$prd_img = ProductsImages::where('prod_id', $products->id)->get();

			if(is_object($prd_img))
			{
				foreach($prd_img as $val_prd_img) 
				{
					@unlink('./uploads/products/' . $group_id . '/' . $val_prd_img->name);
					$val_prd_img->delete();
				}
			}

			ProductsOptions::where('prod_id', object_get($products, 'id', 0))->delete();

			$products->delete();
			return response()->json(['success' => true, 'href' => '/admin/products/group/' . $group_id]);
		}
		else
		{
			return response()->json(['success' => false, 'href' => '/admin/products/group/' . $group_id]);
		}
	}

	public function postDeleteImages($group_id, $id, $img_id = 0)
	{
		$prd_img = ProductsImages::where('id', $img_id)->where('group_id', $group_id)->where('prod_id', $id)->first();

		if(is_object($prd_img))
		{
			$prd_img->delete();

			return response()->json(['success' => true, 'href' => '/admin/products/list/images/' . $group_id . '/' . $id]);
		}
		else
		{
			return response()->json(['success' => false, 'href' => '/admin/products/list/images/' . $group_id . '/' . $id]);
		}
	}

	public function getImages($group_id, $id = 0)
	{
		return view('admin.products_images', [
			'data' => Products::find($id),
			'group_id' => $group_id,
			'breadcrumb' => ProductsGroup::breadcrumb($group_id)
		]);
	}

	public function postUplodImg(Request $request, $group_id, $id = 0)
	{
		if($request->hasFile('images'))
		{
			$patch = 'uploads/products/' . $group_id;

			foreach($request->file('images') as $k_img => $v_img)
			{
				$img_ext = $v_img->extension();
				$name_img = str_random(32) . '.' . $img_ext;
				$v_img->move($patch, $name_img);

				$prd_img = new ProductsImages;
				$prd_img->group_id = $group_id;
				$prd_img->prod_id = $id;
				$prd_img->name = $name_img;
				$prd_img->save();

				unset($name_img);
			}
		}

		return response()->json(['success' => true, 'href' => '/admin/products/list/images/' . $group_id . '/' . $id]);
	}

	public function getTogetherCheaper($group_id, $id = 0)
	{
		$prod = Products::with('options')->find($id);

		return view('admin.products_together_cheaper', [
			'data' => $prod,
			'options' => object_get($prod, 'options'),
			'dataAll' => Products::get()->pluck('fullname', 'id')->toArray(),
			'group_id' => $group_id,
			'breadcrumb' => ProductsGroup::breadcrumb($group_id),
		]);
	}

	public function getRecommended($group_id, $id = 0)
	{
		$prod = Products::with('options')->find($id);

		return view('admin.products_recommended', [
			'data' =>$prod,
			'dataAll' => Products::orderBy('title')->get()->pluck('fullname', 'id')->toArray(),
			'likewisesSelected' => Likewises::where('product_id', $id)->pluck('likewise_id')->toArray(),
			'group_id' => $group_id,
			'breadcrumb' => ProductsGroup::breadcrumb($group_id),
		]);
	}

	public function postSaveRecommended(Request $request, $group_id, $id = 0)
	{
		Likewises::where('product_id', $id)->delete();

		if($request->has('likewises'))
		{
			foreach ($request->input('likewises') as $key => $value) {
				$likewises = new Likewises();
				$likewises->likewise_id = $value;
				$likewises->product_id = $id;
				$likewises->save();
			}
		}

		return response()->json(['success' => true, 'href' => '/admin/products/list/recommended/' . $group_id . '/' . $id]);
	}

	public function postAjaxOptions(Request $request)
	{
		$prodId = $request->input('id');
		$productsOptions = ProductsOptions::where('prod_id', $prodId)->get();
		$option = array();

		foreach ($productsOptions as $value) 
		{
			$option[object_get($value, 'option_id')] = object_get($value, 'option.name');
		}

		return $option;
	}
}
