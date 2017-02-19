<?php

namespace Gstt\Achievements\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Model for the table that will store the data regarding achievement progress and unlocks.
 *
 * @category Model
 * @package  Gstt\Achievements\Model
 * @author   Gabriel Simonetti <simonettigo@gmail.com>
 * @license  MIT License
 * @link     https://github.com/gstt/laravel-achievements
 */
class AchievementProgress extends Model
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'achievement_progress';

    /**
     * The guarded attributes on the model.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'unlocked_at' => 'datetime',
    ];

    /**
     * Get the notifiable entity that the achievement progress belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function achiever()
    {
        return $this->morphTo();
    }

    /**
     * Get the achievement details.
     */
    public function details()
    {
        return $this->belongsto('Gstt\Achievements\Model\AchievementDetails', 'achievement_id');
    }

    /**
     * Checks if the achievement has been unlocked.
     *
     * @return bool
     */
    public function isUnlocked()
    {
        if (!is_null($this->unlockedAt)) {
            return true;
        }
        if ($this->points >= $this->details->points) {
            return true;
        }
        return false;
    }

    /**
     * Overloads save method.
     *
     * @param array $options Options to be passed to the parent class save method.
     *
     * @return bool
     */
    public function save($options = [])
    {
        $recently_unlocked = false;
        if (is_null($this->unlockedAt) && $this->isUnlocked()) {
            $recently_unlocked = true;
            $this->points = $this->details->points;
            $this->unlocked_at = Carbon::now();
        }

        $result = parent::save($options);

        if ($recently_unlocked) {
            // Gets the achievement class for this progress
            $class = $this->details->getClass();

            // Runs the callback set to run when the achievement is unlocked.
            $class->whenUnlocked($this);
        }

        return $result;
    }
}
