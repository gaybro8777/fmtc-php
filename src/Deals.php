<?php
namespace Fmtc; 

use Illuminate\Database\Capsule\Manager as DB;

class Deals
{
	public function get($id)
	{
		// find the deal
		$deal = DB::table('deals')
					->where('nCouponID', $id)
					->first();

		// return deal if found, if not return false
		return ($deal) ? $this->formatDealForReturn($deal) : false;
	}

	public function all($limit = false, $offset = 0)
	{
		$query = $this->getBaseQuery($limit, $offset);

		// fetch all deals
		$deals = $query->get();

		return $this->formatDealsForReturn($deals);
	}

	public function getByCategorySlug($slug, $limit = false, $offset = 0)
	{
		$query = $this->getBaseQuery($limit, $offset);

		$deals = $query->where('deals_categories.cCategorySlug', $slug)
					   ->join('deals_categories', 'deals_categories.nCouponID', '=', 'deals.nCouponID')
					   ->get();

		return $this->formatDealsForReturn($deals);
	}

	public function getByTypeSlug($slug, $limit = false, $offset = 0)
	{
		$query = $this->getBaseQuery($limit, $offset);

		$deals = $query->where('deals_types.cTypeSlug', $slug)
					   ->join('deals_types', 'deals_types.nCouponID', '=', 'deals.nCouponID')
					   ->get();

		return $this->formatDealsForReturn($deals);
	}

	public function getByMerchant($id, $limit = false, $offset = 0)
	{
		$query = $this->getBaseQuery($limit, $offset);

		$deals = $query->where('nMerchantID', $id)->get();

		return $this->formatDealsForReturn($deals);
	}

	public function getByMasterMerchant($id, $limit = false, $offset = 0)
	{
		$query = $this->getBaseQuery($limit, $offset);

		$deals = $query->where('nMasterMerchantID', $id)
					->get();

		return $this->formatDealsForReturn($deals);
	}

	public function getBySearch($search, $limit = false, $offset = 0)
	{
		$query = $this->getBaseQuery($limit, $offset);

		$deals = $query->where(function($query) use ($search) {
			$query->where('nCouponID', 'like', '%' . $search . '%')
				  ->orWhere('cMerchant', 'like', '%' . $search . '%')
				  ->orWhere('cLabel', 'like', '%' . $search . '%');
		})->get();

		return $this->formatDealsForReturn($deals);
	}

	public function getBaseQuery($limit = false, $offset = 0)
	{
		$query = DB::table('deals')
					->orderBy('fRating', 'desc');

		if ($limit !== false) {
			$query->skip($offset);
			$query->take($limit);
		}

		return $query;
	}

	protected function formatDealsForReturn($deals)
	{
		// make a deals collection to access helpful collection methods
		$deals = collect($deals);

		// fetch the deal's categories
		$categories = collect(DB::table('categories')
						->wherein('deals_categories.nCouponID', $deals->pluck('nCouponID'))
						->join('deals_categories', 'deals_categories.cCategorySlug', '=', 'categories.cSlug')
						->get());

		// fetch the deal's types
		$types = collect(DB::table('types')
						->wherein('deals_types.nCouponID', $deals->pluck('nCouponID'))
						->join('deals_types', 'deals_types.cTypeSlug', '=', 'types.cSlug')
						->get());

		// attach categories and types to the belonging deal
		$deals = $deals->each(function($deal) use ($categories, $types) {
			$deal->aCategories = $categories->where('nCouponID', $deal->nCouponID)->toArray();
			$deal->aTypes = $types->where('nCouponID', $deal->nCouponID)->toArray();
		});

		return $deals->toArray();	
	}

	protected function formatDealForReturn($deal)
	{
		// attach it's categories
		$deal->aCategories = DB::table('deals_categories')
								->where('deals_categories.nCouponID', $id)
								->join('categories', 'categories.cSlug', '=', 'deals_categories.cCategorySlug')
								->get();

		// attach it's types
		$deal->aTypes = DB::table('deals_types')
							->where('deals_types.nCouponID', $id)
							->join('types', 'types.cSlug', '=', 'deals_types.cTypeSlug')
							->get();

		return $deal;
	}
}