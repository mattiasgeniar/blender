<?php

namespace App\Http\Controllers\Back;

use App\Services\Auth\Back\User;
use App\Services\Auth\Back\Enums\UserRole;
use App\Http\Requests\Back\BackUserRequest;
use App\Services\Auth\Back\Enums\UserStatus;
use App\Services\Auth\Back\Events\UserCreated;

class AdministratorsController
{
    public function index()
    {
        $users = User::all();

        return view('back.administrators.index')->with(compact('users'));
    }

    public function create()
    {
        return view('back.administrators.create', ['user' => new User()]);
    }

    public function store(BackUserRequest $request)
    {
        $user = new User();

        $user->email = $request->get('email');
        $user->first_name = $request->get('first_name');
        $user->last_name = $request->get('last_name');
        $user->locale = $request->get('locale', 'nl');

        if ($request->has('password')) {
            $user->password = bryct($request->get('password'));
        }

        $user->role = UserRole::ADMIN;
        $user->status = UserStatus::ACTIVE;

        $user->save();

        $eventDescription = $this->getEventDescriptionFor('created', $user);
        activity()->on($user)->log($eventDescription);
        flash()->success(strip_tags($eventDescription).'. '.__('Er werd een mail verstuurd naar de gebruiker waarmee een wachtwoord kan ingesteld worden'));

        event(new UserCreated($user));

        return redirect(action('Back\AdministratorsController@index', ['role' => $user->role]));
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);

        return view('back.administrators.edit')->with(compact('user'));
    }

    public function update($id, BackUserRequest $request)
    {
        $user = User::findOrFail($id);

        $user->email = $request->get('email');
        $user->first_name = $request->get('first_name');
        $user->last_name = $request->get('last_name');
        $user->locale = $request->get('locale', 'nl');

        if ($request->has('password')) {
            $user->password = $request->get('password');
        }

        $user->save();

        $eventDescription = $this->getEventDescriptionFor('updated', $user);
        activity()->on($user)->log($eventDescription);
        flash()->success(strip_tags($eventDescription));

        return redirect()->action('Back\AdministratorsController@index');
    }

    public function activate($id)
    {
        $user = User::findOrFail($id);

        $user->activate();

        $eventDescription = $this->getEventDescriptionFor('activated', $user);
        activity($eventDescription);
        flash()->success(strip_tags($eventDescription));

        return back();
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        $eventDescription = $this->getEventDescriptionFor('deleted', $user);

        $user->delete();

        activity()->log($eventDescription);
        flash()->success(strip_tags($eventDescription));

        return redirect()->action('Back\AdministratorsController@index');
    }

    protected function getEventDescriptionFor(string $event, User $user): string
    {
        $name = sprintf(
            '<a href="%s">%s</a>',
            action('Back\AdministratorsController@edit', [$user->id]),
            $user->email
        );

        $action = '';

        if ($event === 'created') {
            $action = __('werd aangemaakt');
        }

        if ($event === 'updated') {
            $action = __('werd gewijzigd');
        }

        if ($event === 'deleted') {
            $name = $user->email;
            $action = __('werd verwijderd');
        }

        return __('Administrator').' '.$name.' '.$action;
    }
}
