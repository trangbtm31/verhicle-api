<?php

namespace App\Http\Controllers;

use App\User;
use App\Rating;

use Illuminate\Http\Request;

class RatingController extends Controller
{
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		//
	}

	public function doVote(Request $request)
	{
		$rating = new Rating();
		$totalRatingValue = 0;
		$rating->create([
			'user_id' => $request->get('user_id'),
			'journey_id' => $request->get('journey_id'),
			'rating_value' => $request->get('rating_value'),
		]);
		$totalRatings = $rating->where('journey_id', '=', $request->get('journey_id'))->get();
		foreach ($totalRatings as $totalRating) {
			$totalRatingValue += $totalRating;
		}

    }

	//
}
