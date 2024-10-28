<?php

namespace App\Livewire;

use App\Models\Conversation;
use App\Models\User;
use Livewire\Component;
use Unsplash\Photo;
use App\Traits\AvatarTrait;

class Users extends Component
{
    use AvatarTrait;
    public $users;

    public function mount()
    {
        $this->users = User::all();
    }

    /* public function getRandomAvatar($user)
    {

        $cacheKey = 'user_avatar_' . $user->id;

    // Check if avatar is cached
    if (cache()->has($cacheKey)) {
        return cache()->get($cacheKey);
    }

        $photo = Photo::random(['query' => 'person', 'orientation' => 'squarish']);
        $avatarUrl = $photo->urls['small'];
        cache()->put($cacheKey, $avatarUrl, now()->addDay());
        return $avatarUrl;
    } */

    public function message($userId)
    {
        $authenticatedUserId = auth()->id();
        #check conversation exists

        $existingConversation= Conversation::where(function ($query) use($authenticatedUserId, $userId) {
            $query->where('sender_id', $authenticatedUserId)
                  ->where('receiver_id', $userId);
        })->orWhere(function ($query) use($authenticatedUserId, $userId) {
            $query->where('sender_id', $userId)
                  ->where('receiver_id', $authenticatedUserId);
        })->first();

        if ($existingConversation) {
            return redirect()->route('chat', ['query'=>$existingConversation->id]);
        }
        #Create conversation
        $createdConversation= Conversation::create([
            // dd($userId),
            'sender_id' => $authenticatedUserId,
            'receiver_id' => $userId,
        ]);
        return redirect()->route('chat', ['query'=>$createdConversation->id]);

    }
    public function render()
    {
        return view('livewire.users', ['users' => $this->users->where('id','!=',auth()->id())->get('id') ]);
    }
}
