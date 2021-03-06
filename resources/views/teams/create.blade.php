@extends('layouts.app')

@section('title', 'The Alumni League Teams')

@section('content')
	<div class="container-fluid bgrd3">

		<div class="row">

			<div class="col-12 my-3 text-center">
				<a class="btn btn-rounded mdb-color darken-3 white-text" type="button" href="{{ request()->query() == null ? route('league_teams.index') : route('league_teams.index', ['season' => request()->query('season'), 'year' => request()->query('year')]) }}">All Teams</a>
			</div>

			<div class="col-12 col-md-8 mx-auto py-4">

				<div class="text-center coolText1">
					<div class="text-center p-4 card rgba-deep-orange-light white-text my-3" id="">
						<h1 class="h1-responsive text-uppercase">{{ $showSeason->name }}</h1>
					</div>
				</div>

				<div class="text-center coolText1">
					<h3 class="h-responsive">Total Teams: {{ $totalTeams }}</h3>
				</div>
				
				<!--Card-->
				<div class="card card-cascade mb-4 reverse wider">
					<!--Card image-->
					<div class="view">
						<img src="{{ $defaultImg }}" class="img-fluid mx-auto" alt="photo">
					</div>
					<!--Card content-->
					<div class="card-body rgba-white-strong rounded z-depth-1-half">
						<!--Title-->
						<h2 class="card-title h2-responsive text-center">Create New Team</h2>

						<!-- Create Form -->
						<form action="{{ action('LeagueTeamController@store', ['season' => $showSeason->id]) }}" method="POST" class="" enctype="multipart/form-data">

							{{ csrf_field() }}

							<div class="md-form">
								<input type="text" name="team_name" class="form-control" value="{{ old('team_name') }}" />
								<label for="team_name">Team Name</label>
							</div>
							
							@if($errors->has('team_name'))
								<div class="md-form-errors red-text">
									<p class=""><i class="fa fa-exclamation" aria-hidden="true"></i>&nbsp;{{ $errors->first('team_name') }}</p>
								</div>
							@endif
							
							<div class="input-form">
								<label for="fee_paid" class="d-block">League Fee Paid</label>
								<div class="">
									<button class="btn inputSwitchToggle green active" type="button">Yes
										<input type="checkbox" name="fee_paid" class="hidden" value="Y" checked hidden />
									</button>
									
									<button class="btn inputSwitchToggle grey" type="button">No
										<input type="checkbox" name="fee_paid" class="hidden" value="N" hidden />
									</button>
								</div>
							</div>
							<div class="md-form text-center">
								<button type="submit" class="btn blue lighten-1 white-text">Create Team</button>
							</div>
						</form>
					</div>
				</div>
				<!--/.Card-->
			</div>
		</div>
	</div>
@endsection