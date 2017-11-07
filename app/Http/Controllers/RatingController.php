<?php

namespace App\Http\Controllers;

use App\User;
use App\Rating;

use Illuminate\Http\Request;

class RatingController extends Controller
{
    /**
     * @var Rating
     */
    protected $rating;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
        $this->rating = new Rating();
    }

    public function doVote(Request $request)
    {
        $rating = $this->rating;
        $totalRatingValue = 0;
        $rating->create(
            [
                'user_id' => $request->get('user_id'),
                'journey_id' => $request->get('journey_id'),
                'rating_value' => $request->get('rating_value'),
                'comment' => $request->get('comment')
            ]
        );
        $ratingValues = $rating->where('journey_id', '=', $request->get('journey_id'))->get();
        $totalRating = $ratingValues->count();
        $lastRating = $rating->orderBy('id', 'desc')->first();

        foreach ($ratingValues as $ratingValue) {
            $totalRatingValue += $ratingValue->rating_value;
        }
        $argRating = round($totalRatingValue / $totalRating, 1);
        $result = [
            'update_date' => $lastRating->updated_at,
            'total_rating' => $argRating,
        ];

        return $this->success(
            "rating_info",
            $result,
            200
        );

    }

    public function getCommentList($journey_id)
    {
        $rating = $this->rating;
        return $rating->where('journey_id', '=', $journey_id)->orderBy('id', 'desc')->get();
    }

    //
}
