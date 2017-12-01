<?php

namespace App\Http\Controllers;

use App\User;
use App\Rating;
use App\Journeys;

use Illuminate\Http\Request;

class RatingController extends Controller
{
    /**
     * @var Rating
     */
    protected $rating;

    /**
     * @var mixed
     */
    protected $user;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        //
        $this->rating = new Rating();
        $this->user = $request->user();

    }

    public function doVote(Request $request)
    {
        $rating = $this->rating;
        $user = $this->user;
        $journey = new Journeys();
        $journeyId = $request->get('journey_id');
        $rating->create(
            [
                'user_id' => $user->id,
                'journey_id' => $journeyId,
                'rating_value' => $request->get('rating_value'),
                'comment' => $request->get('comment')
            ]
        );

        $argRating = $rating->getJourneyRating($journeyId);
        $journeyInfo = $journey->find($journeyId);

        $journeyInfo->rating_value = $argRating;

        $result = [
            'total_rating' => $argRating,
            'user_info' => $user
        ];

        return $this->success(
            200,
            "rating_info",
            $result
        );

    }

    public function getCommentList($journey_id)
    {
        $rating = $this->rating;
        return $rating->where('journey_id', '=', $journey_id)->orderBy('id', 'desc')->get();
    }

    //
}
