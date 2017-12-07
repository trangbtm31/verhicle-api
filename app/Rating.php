<?php
/**
 * Created by PhpStorm.
 * User: trang
 * Date: 10/22/17
 * Time: 8:42 PM
 */

namespace App;

use Illuminate\Database\Eloquent\Model;


class Rating extends Model
{
    protected $fillable = ['user_id', 'journey_id', 'rating_value','comment' ];

    protected $hidden = ['id', 'created_at', 'updated_at'];

    /**
     * Define a BelongsTo relationship with App\User
     */
    public function users()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * @param $journeyId
     * @return float
     */
    public function getJourneyRating($journeyId) {
        $ratingValues = $this->where('journey_id', '=', $journeyId)->get();
        $totalRating = $ratingValues->count();
        $totalRatingValue = 0;
        //$lastRating = $this->orderBy('id', 'desc')->first();

        foreach ($ratingValues as $ratingValue) {
            $totalRatingValue += $ratingValue->rating_value;
        }
        $argRating = round($totalRatingValue / $totalRating, 1);

        return $argRating;
    }

}