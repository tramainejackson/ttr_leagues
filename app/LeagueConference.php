<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeagueConference extends Model
{

	/**
	 * Get the teams for the conference object.
	 */
	public function teams()
	{
		return $this->hasMany('App\LeagueTeam');
	}
}
