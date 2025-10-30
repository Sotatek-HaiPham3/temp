<?php

namespace App\Http\Requests;

use App\Models\Game;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use DB;
use Auth;
use App\Consts;
use App\Models\RoomCategory;
use App\Models\VoiceGroupManager;

class CreateVoiceChatRoomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $isAdmin = VoiceGroupManager::where('user_id', Auth::id())
            ->where('role', Consts::VOICE_GROUP_ROLE_ADMIN)
            ->exists();
        $gameIdChatting = Game::where('type', Consts::CATEGORY_TYPE_CHAT)->first();
        $isChattingCategory = $this->game_id === $gameIdChatting->id;
        $isCommunityCategory = !!$this->community_id;
        $isInviteFriend = !!$this->friend_id;
        $isChattingRoom = !$isChattingCategory && $this->type === Consts::ROOM_TYPE_HANGOUT;
        $roomTypes = $this->getAvailableRoomTypes($isAdmin, $isChattingCategory);

        return [
            'game_id'           => $isInviteFriend ? '' : 'required_without:community_id|exists:room_categories,game_id',
            'is_private'        => $isCommunityCategory || $isInviteFriend ? '' : 'required',
            'type'              => ($isChattingCategory && !$isAdmin) || $isCommunityCategory || $isInviteFriend ? '' : ['required', Rule::in($roomTypes)],
            'title'             => 'nullable|max:64',
            'size'              => !$isChattingCategory && !$isChattingRoom && !$isCommunityCategory && !$isInviteFriend ? 'required' : '',
            'topic'             => 'nullable|max:128',
            'friend_id'         => 'nullable|array',
        ];
    }

    private function getCategoryTypes () {
        return RoomCategory::where('game_id', $this->game_id)->value('type');
    }

    private function getAvailableRoomTypes($isAdmin, $isChattingCategory)
    {
        $data = [Consts::ROOM_TYPE_HANGOUT, Consts::ROOM_TYPE_COMMUNITY];
        if ($isAdmin) {
            array_push($data, Consts::ROOM_TYPE_AMA);
        }

        if (!$isChattingCategory) {
            array_push($data, Consts::ROOM_TYPE_PLAY);
        }

        return $data;
    }
}
