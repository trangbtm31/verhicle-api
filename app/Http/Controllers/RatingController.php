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
        $userId = $user->id;
        $journey = new Journeys();
        $journeyId = $request->get('journey_id');

        $journeyInfo = $journey->find($journeyId);
        $userInfo = $user->find($userId);
        if($userId != $journeyInfo->hiker_id && $userId != $journeyInfo->driver_id) {
            return $this->error(
                1,
                'You didn\'t join to this journey',
                200
            );
        }

        $rating->create(
            [
                'user_id' => $user->id,
                'journey_id' => $journeyId,
                'rating_value' => $request->get('rating_value'),
                'comment' => $request->get('comment')
            ]
        );

        $journeyList = $rating->where('journey_id', '=', $journeyId)->get();
        $userHikerRatingList = $journey->where('hiker_id', '=', $userId)->get();
        $userDriverRatingList = $journey->where('driver_id', '=', $userId)->get();

        $argJourneyRating = $rating->getAvgRating($journeyList);
        $argHikerRating = $rating->getAvgRating($userHikerRatingList);
        $argDriverRating = $rating->getAvgRating($userDriverRatingList);

        $journeyInfo->rating_value = $argJourneyRating;
        $userInfo->avg_hiker_vote = $argHikerRating;
        $userInfo->avg_driver_vote = $argDriverRating;
        $journeyInfo->save();

        $result = [
            'total_rating' => $argJourneyRating,
            'user_info' => $user,

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
