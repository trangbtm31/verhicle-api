<?php

namespace App\Http\Controllers;

use App\User;
use App\Rating;

use Illuminate\Http\Request;

class RatingController extends Controller
{
    protected $journeyId;

    protected $userId;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        //
        $this->userId = $request->get('user_id');
        $this->journeyId = $request->get('journey_id');
    }

    public function doVote(Request $request)
    {
        Request::create( [
                'user_id' => $this->userId,
                'journey_id' => $this->journeyId,
                'rating_value' => $request->get('rating_value'),
                'comment' => $request->get('comment')
            ]
        );

    }

    //
}
