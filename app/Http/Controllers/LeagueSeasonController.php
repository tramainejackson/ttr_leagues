<?php

namespace App\Http\Controllers;

use App\LeagueConference;
use App\LeagueDivision;
use App\PlayerProfile;
use App\LeagueProfile;
use App\LeagueSchedule;
use App\LeagueStanding;
use App\LeaguePlayer;
use App\LeagueTeam;
use App\LeagueStat;
use App\LeagueSeason;
use App\LeagueRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Intervention\Image\ImageManagerStatic as Image;

class LeagueSeasonController extends Controller
{

	public $showSeason;

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->middleware('auth')->except(['index', 'archive_show', 'archive_index']);

		$this->showSeason = LeagueProfile::find(2)->seasons()->showSeason();
	}

	public function get_season() {
		return $this->showSeason;
	}

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
//    	dd($this->showSeason);
    	$showSeason = $this->showSeason;
    	$completedSeasons = $this->showSeason->league_profile->seasons()->completed();
	    $ageGroups = explode(' ', $this->showSeason->league_profile->age);
	    $compGroups = explode(' ', $this->showSeason->league_profile->comp);
	    $showSeasonSchedule = $this->showSeason->games()->upcomingGames()->get();
	    $showSeasonStat = $this->showSeason->stats()->get();
	    $showSeasonTeams = $this->showSeason->league_teams;
	    $showSeasonUnpaidTeams = $this->showSeason->league_teams()->unpaid();
	    $showSeasonPlayers = $this->showSeason->league_players;
	    $showSeasonConferences = $this->showSeason->conferences;
	    $showSeasonDivisions = $this->showSeason->divisions;
	    $leagueRules = $this->showSeason->league_rules;
	    $allStarGame = $this->showSeason->games()->where('all_star_game', 'Y')->first();
	    $allGames = $this->showSeason->games;
	    $allTeams = $this->showSeason->league_teams;
	    $playoffSettings = $this->showSeason->playoffs;
	    $nonPlayInGames = $this->showSeason->games()->playoffNonPlayinGames()->get();
	    $playInGames = $this->showSeason->games()->playoffPlayinGames()->get();

    	if($showSeason !== null || $completedSeasons !== null) {
		    return view('seasons.index', compact('showSeason', 'ageGroups', 'compGroups', 'showSeasonSchedule', 'showSeasonStat', 'showSeasonTeams', 'showSeasonPlayers', 'showSeasonUnpaidTeams', 'showSeasonConferences', 'showSeasonDivisions', 'leagueRules', 'allStarGame', 'playoffSettings', 'allGames', 'allTeams', 'nonPlayInGames', 'playInGames'));
	    } else {
		    return view('seasons.no_season');
	    }
    }
	
	/**
     * Store a new season for the logged in league.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
    	$season = new LeagueSeason();
		$season->league_profile_id = $request->league_id;
		$season->season = $request->season;
		$season->name = $request->name;
		$season->year = $request->year;
		$season->age_group = $request->age_group;
		$season->comp_group = $request->comp_group;
		$season->league_fee = $request->league_fee;
		$season->ref_fee = $request->ref_fee;
		$season->location = $request->location;
	    $season->has_conferences = $request->conferences;
	    $season->has_divisions = $request->divisions;
		$season->active = 'Y';
		$season->paid = 'Y';
		
		if($season->save()) {
			if($season->playoffs()->create([])) {
				$newSeason = $season->id;

				$season->conferences()->createMany([
					[
						'conference_name' => 'Conference A',
						'league_season_id' => $newSeason,
					],
					[
						'conference_name' => 'Conference B',
						'league_season_id' => $newSeason,
					],
				]);

				$season->divisions()->createMany([
					[
						'division_name' => 'Division A',
						'league_season_id' => $newSeason,
					],
					[
						'division_name' => 'Division B',
						'league_season_id' => $newSeason,
					],
					[
						'division_name' => 'Division C',
						'league_season_id' => $newSeason,
					],
					[
						'division_name' => 'Division D',
						'league_season_id' => $newSeason,
					],
				]);

				return [$newSeason, "New Season Added Successfully"];
			}
		}
    }

	/**
	 * Show the leagues archived season.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function archive_index() {
		// Get the season to show
		$showSeason = $this->showSeason;

		// Resize the default image
		Image::make(public_path('images/commissioner.jpg'))->resize(800, null, 	function ($constraint) {
			$constraint->aspectRatio();
		}
		)->save(storage_path('app/public/images/lg/default_img.jpg'));
		$defaultImg = asset('/storage/images/lg/default_img.jpg');

		return view('archives.index', compact('showSeason', 'defaultImg'));
	}

	/**
	 * Show the leagues archived season.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function archive_show(LeagueSeason $season) {
		// Get the season to show
		$showSeason = $this->showSeason;
		$archiveSeason = $season;
		$standings = $archiveSeason->standings()->seasonStandings()->get();
		$playersStats = $archiveSeason->stats()->allFormattedStats();

		// Resize the default image
		Image::make(public_path('images/commissioner.jpg'))->resize(800, null, 	function ($constraint) {
			$constraint->aspectRatio();
		}
		)->save(storage_path('app/public/images/lg/default_img.jpg'));
		$defaultImg = asset('/storage/images/lg/default_img.jpg');

		return view('archives.show', compact('showSeason', 'archiveSeason', 'standings', 'playersStats', 'defaultImg'));
	}
	
	/**
     * Store a new season for the logged in league.
     *
     * @return \Illuminate\Http\Response
     */
    public function create_playoffs(Request $request, LeagueSeason $league_season) {
		$createPlayoffs = $this->showSeason->create_playoff_settings();
		
		return redirect()->back()->with(['status' => $createPlayoffs]);
    }
	
	/**
     * Store a new season for the logged in league.
     *
     * @return \Illuminate\Http\Response
     */
    public function complete_season(Request $request, LeagueSeason $league_season) {
//    	dd($request);
		$this->showSeason->completed = 'Y';
		$this->showSeason->active = 'N';
		
		if($this->showSeason->save()) {
			return redirect()->action('LeagueSeasonController@archive_show', ['season' => $this->showSeason->id])->with(['status' => 'Season Completed']);
		}
    }
	
	/**
     * Store a new season for the logged in league.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request) {
//		dd($request);

		$this->showSeason->name = $request->name;
		$this->showSeason->comp_group = $request->comp_group;
		$this->showSeason->age_group = $request->age_group;
		$this->showSeason->league_fee = $request->leagues_fee;
		$this->showSeason->ref_fee = $request->ref_fee;
		$this->showSeason->location = $request->leagues_address;
		$this->showSeason->has_conferences = $request->conferences;
		$this->showSeason->has_divisions = $request->divisions;
	    $seasonRules = $this->showSeason->league_rules;
//	    dd($seasonRules);

	    if($this->showSeason->has_conferences == 'Y') {
		    if($this->showSeason->conferences->isEmpty()) {
		    	$conference1 = new LeagueConference();
		    	$conference2 = new LeagueConference();

		    	// Add season id
		    	$conference1->league_season_id = $this->showSeason->id;
		    	$conference2->league_season_id = $this->showSeason->id;

		    	// Add conference name
			    $conference1->conference_name = $request->conference_name[0] != '' ? $request->conference_name[0] : 'Conference A';
			    $conference2->conference_name = $request->conference_name[1] != '' ? $request->conference_name[1] : 'Conference B';

			    if($conference1->save()) {
				    if($conference2->save()) {
				    }
			    }
		    } else {
			    foreach($this->showSeason->conferences as $key => $conference) {
				    $conference->conference_name = $request->conference_name[$key];

				    if($conference->save()) {}
			    }
		    }
	    }

	    if($this->showSeason->has_divisions == 'Y') {
		    if($this->showSeason->conferences->isEmpty()) {
				$division1 = new LeagueDivision();
				$division2 = new LeagueDivision();
				$division3 = new LeagueDivision();
				$division4 = new LeagueDivision();

			    // Add season id
			    $division1->league_season_id = $this->showSeason->id;
			    $division2->league_season_id = $this->showSeason->id;
			    $division3->league_season_id = $this->showSeason->id;
			    $division4->league_season_id = $this->showSeason->id;

			    // Add division name
			    $division1->division_name = $request->division_name[0] != '' ? $request->division_name[0] : 'Division A';
			    $division2->division_name = $request->division_name[1] != '' ? $request->division_name[1] : 'Division B';
			    $division3->division_name = $request->division_name[2] != '' ? $request->division_name[2] : 'Division C';
			    $division4->division_name = $request->division_name[3] != '' ? $request->division_name[3] : 'Division D';

			    if($division1->save()) {
				    if($division2->save()) {
					    if($division3->save()) {
						    if($division4->save()) {

						    }
					    }
				    }
			    }
		    } else {
			    foreach($this->showSeason->divisions as $key => $division) {
				    $division->division_name = $request->division_name[$key];

				    if($division->save()) {}
			    }
		    }
	    }

		if($this->showSeason->save()) {
			// Add new rules
			if(isset($request->new_rule)) {
				foreach($request->new_rule as $key => $newRule) {
					$createRule = new LeagueRule();
					$createRule->rule = $newRule;
					$createRule->league_season_id = $this->showSeason->id;
					$createRule->league_profile_id = $this->showSeason->league_profile->id;

					if($createRule->rule != null && $createRule->rule != '') {
						// Save the new rule
						if ($createRule->save()) {
						}
					}
				}
			}

		    //Updates league rules
			if($seasonRules) {
				foreach($seasonRules as $key => $seasonRule) {
					$seasonRule->rule = $request->rule[$key];

					if($seasonRule->save()) {}
				}
			}

			return redirect()->back()->with(['status' => 'Season Updated Successfully']);
		}
    }
	
	/**
     * Show the application about us page for public.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit() {
        
    }

	/**
     * Show the application about us page for public.
     *
     * @return \Illuminate\Http\Response
     */
    public function show() {

    }

	/**
     * Show the application about us page for public.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy_rule(Request $request, LeagueRule $ruleID) {
		// Delete Rule
	    if($ruleID->delete()) {
	    	return redirect()->back()->with('status', 'Rule Deleted Successfully');
	    }
    }

	/**
     * Show the application about us page for public.
     *
     * @return \Illuminate\Http\Response
     */
    public function create_all_star_team(Request $request) {
    	// createAllStarGame will return a status of completed or denied
	    $completeCheck = $this->showSeason->createAllStarGame($this->showSeason->id);

	    return redirect()->back()->with('status', $completeCheck);
    }
}
