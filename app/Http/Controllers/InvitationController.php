<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvitationController
{
    private function invitation(string $token): Invitation
    {
        return Invitation::where('token_hash', hash('sha256', $token))->whereNull('accepted_at')->where('expires_at', '>', now())->firstOrFail();
    }

    public function show(string $token)
    {
        $invitation = $this->invitation($token);

        return view('invitation', compact('invitation', 'token'));
    }

    public function accept(Request $request, string $token)
    {
        $request->validate(['password' => 'required|confirmed|min:12']);
        DB::transaction(function () use ($request, $token) {
            $i = Invitation::where('token_hash', hash('sha256', $token))->lockForUpdate()->firstOrFail();
            abort_unless(! $i->accepted_at && $i->expires_at->isFuture(), 410);
            $u = User::create(['name' => $i->name, 'email' => $i->email, 'password' => $request->string('password')->toString()]);
            $u->forceFill(['email_verified_at' => now(), 'region_id' => $i->region_id])->save();
            $u->assignRole($i->role);
            if ($i->institution_id) {
                $u->institutions()->attach($i->institution_id, ['position' => $i->position, 'is_authorized' => $i->role === 'hei_submitter', 'verified_at' => now(), 'verified_by' => $i->invited_by]);
            }
            $i->update(['accepted_at' => now()]);
        });

        return redirect('/')->with('status', 'Account created. Sign in to your panel and set up MFA.');
    }
}
