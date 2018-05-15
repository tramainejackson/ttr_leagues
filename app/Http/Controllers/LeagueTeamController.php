<?php

namespace App\Http\Controllers;

use App\PlayerProfile;
use App\LeagueProfile;
use App\LeagueSchedule;
use App\LeagueStanding;
use App\LeaguePlayer;
use App\LeagueTeam;
use App\LeagueStat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeagueTeamController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
		// Get the season to show
		$showSeason = $this->find_season(request());
		$activeSeasons = $showSeason->league_profile->seasons()->active()->get();
		$seasonTeams = $showSeason->league_teams;

		return view('teams.index', compact('showSeason', 'activeSeasons', 'seasonTeams'));
    }
	
	/**
     * Show the application create team page.
     *
     * @return \Illuminate\Http\Response
    */
    public function create()
    {
		// Get the season to show
		$showSeason = $this->find_season(request());
		$activeSeasons = $showSeason->league_profile->seasons()->active()->get();

		return view('teams.create', compact('showSeason', 'activeSeasons'));
    }
	
	/**
     * Show the application create team page.
     *
     * @return \Illuminate\Http\Response
    */
    public function store(Request $request)
    {
		$this->validate($request, [
			'team_name' => 'required',
		]);
		
		// Get the season to show
		$showSeason = $this->find_season(request());
		$activeSeasons = $showSeason->league_profile->seasons()->active()->get();
		
		// Create a new team for the selected season
		$team = $showSeason->league_teams()->create([
			'team_name' => ucwords(strtolower($request->team_name)),
			'fee_paid' => $request->fee_paid,
			'leagues_profile_id' => $showSeason->league_profile->id,
		]);

		if($team) {
			return redirect()->action('LeagueTeamController@edit', ['team' => $team->id])->with('status', 'New Team Added Successfully');
		} else {}
    }
	
	/**
     * Show the application create team page.
     *
     * @return \Illuminate\Http\Response
    */
    public function edit(LeagueTeam $league_team)
    {
		// Get the season to show
		$showSeason = $this->find_season(request());

		return view('teams.edit', compact('league_team', 'showSeason'));
    }
	
	/**
     * Update the teams information.
     *
     * @return \Illuminate\Http\Response
    */
    public function update(Request $request, LeagueTeam $league_team)
    {
		// Get the season to show
		$showSeason = $this->find_season(request());

		$league_team->team_name = $request->team_name;
		$league_team->fee_paid = $request->fee_paid;
		$team_players = $league_team->players;
		$team_standing = $league_team->standings;
		$team_home_games = $league_team->home_games;
		$team_away_games = $league_team->away_games;

		if($league_team->save()) {
			// Add new players
			if(isset($request->new_player_name)) {
				foreach($request->new_player_name as $key => $newPlayerName) {
					$newPlayer = new LeaguePlayer();
					$newPlayer->team_name = $request->team_name;
					$newPlayer->player_name = $newPlayerName;
					$newPlayer->jersey_num = $request->new_jers_num[$key];
					$newPlayer->email = $request->new_player_email[$key];
					$newPlayer->phone = $request->new_player_phone[$key];
					$newPlayer->team_captain = 'N';
					$newPlayer->league_team_id = $league_team->id;
					$newPlayer->league_season_id = $showSeason->id;
					
					// Save the new team player
					if($newPlayer->save()) {
						// If this team has any team stats, then
						// add each new player to that games stats
						if($league_team->home_games->merge($league_team->away_games)->isNotEmpty()) {
							$games = $league_team->home_games->merge($league_team->away_games);
							
							foreach($games as $game) {
								// Check and see if the game has stats added yet
								// Add player to that games stats if exist
								if($game->player_stats->isNotEmpty()) {
									$newPlayerStat = new LeagueStat();
									$newPlayerStat->league_teams_id = $league_team->id;
									$newPlayerStat->league_season_id = $showSeason->id;
									$newPlayerStat->league_schedule_id = $game->id;
									$newPlayerStat->league_player_id = $newPlayer->id;
									$newPlayerStat->game_played = 0;
									
									if($newPlayerStat->save()) {}
								}
							}
						}
					}
				}
			}
			// Updates team players
			if($team_players) {
				foreach($team_players as $key => $player) {
					$player->team_captain = str_ireplace('captain_', '', $request->team_captain) == $player->id ? 'Y' : 'N';
					$player->team_name = $request->team_name;
					$player->player_name = $request->player_name[$key];
					$player->jersey_num = $request->jersey_num[$key];
					$player->email = $request->player_email[$key];
					$player->phone = $request->player_phone[$key];
					
					if($player->save()) {}
				}
			}
			
			// Update team standings
			if($team_standing) {
				$team_standing->team_name = $request->team_name;
				if($team_standing->save()) {}
			}
			
			// Update games on the calendar
			if($team_home_games) {
				foreach($team_home_games as $home_game) {
					$home_game->home_team = $request->team_name;
					
					if($home_game->save()) {}
				}
			}
			
			if($team_away_games) {
				foreach($team_away_games as $away_game) {
					$away_game->away_team = $request->team_name;
					
					if($away_game->save()) {}
				}
			}
			
			// Update player stats
			return redirect()->back()->with('status', 'Team Updated');
		}
    }
	
	/**
     * Check for a query string and get the current season.
     *
     * @return seaon
    */
	public function find_season(Request $request) {
		$league = Auth::user()->leagues_profiles->first();
		
		$showSeason = '';
		
		if($request->query('season') != null && $request->query('year') != null) {
			$showSeason = $league->seasons()->active()->find($request->query('season'));
		} else {
			$showSeason = $league->seasons()->active()->first();
		}
		
		return $showSeason;
	}
}
