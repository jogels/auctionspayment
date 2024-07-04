<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\MemberRequest;
use App\Models\Member\FollowedStore;
use App\Models\Member\Member;
use App\Notifications\Member\ChangePinNotification;
use App\Notifications\User\ChangePasswordNotification;
use App\Services\Member\MemberService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Display user data
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $response['user'] = $user;
        //$response['addresses'] = $user->addresses;
        if (!empty($user->member)) {
            $response['member'] = $user->member;
        }

        return response()->json($response);
    }

    /**
     * Update user info
     *
     * @param \App\Http\Requests\Member\MemberRequest $request
     * @param \App\Services\Member\MemberService $service
     * @return \Illuminate\Http\Response
     */
    public function store(MemberRequest $request, MemberService $service)
    {
        $user = $request->user();

        if (!empty($user->member)) {
            $member = $user->member;
        } else {
            $member = new Member();
        }
        $member = $service->submit($member, $request);

        return response()->json([
            'user' => $user,
            'member' => $member,
            'message' => 'Successfully Submit Member Info'
        ]);
    }

    /**
     * Change user password
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();
        $validation = Validator::make(
            $request->only('password', 'password_confirm'),
            [
                'password' => 'required|min:8',
                'password_confirm' => 'required|min:8|same:password'
            ],
            [],
            [
                'password' => 'Password',
                'password_confirm' => 'Password Confirm'
            ]
        );
        if ($validation->fails()) {
            $response['status'] = false;
            $response['errors'] = $validation->errors()->all();

            throw new HttpResponseException(response()->json($response, 422));
        }
        $user->password = Hash::make($request->password);
        $user->save();
        // Send email notification
        $user->notify(new ChangePasswordNotification($user));

        return response()->json([
            'user' => $user,
            'message' => 'Successfully change password!'
        ]);
    }

    /**
     * Check current member pin
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function checkPin(Request $request)
    {
        $member = $request->user()->member;
        if (empty($member->pin)) {
            $response['status'] = false;
            $response['message'] = 'Anda belum mengisi PIN!';

            throw new HttpResponseException(response()->json($response, 422));
        }
        if (!empty($member->pin) && $member->pin == $request->pin) {
            $response['status'] = true;
            $response['message'] = 'PIN anda Valid!';
        } else {
            $response['status'] = false;
            $response['message'] = 'PIN anda salah!';

            throw new HttpResponseException(response()->json($response, 422));
        }

        return response()->json($response);
    }

    /**
     * Change member pin
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function changePin(Request $request)
    {
        $validation = Validator::make(
            $request->all(),
            [
                'pin' => 'required|numeric|same:pin_confirm',
                'pin_confirm' => 'required|numeric|same:pin'
            ],
            [],
            [
                'pin' => 'PIN',
                'pin_confirm' => 'Confirm PIN',
            ]
        );
        if ($validation->fails()) {
            $response['status'] = false;
            $response['errors'] = $validation->errors()->all();

            throw new HttpResponseException(response()->json($response, 422));
        }

        $user = $request->user();
        $member = $user->member;
        $member->pin = $request->pin;
        $member->save();
        // Send email notification
        $user->notify(new ChangePinNotification($user));

        return response()->json([
            'user' => $user,
            'member' => $member,
            'message' => 'Successfully change PIN!'
        ]);
    }

    /**
     * Get all member followed_stores
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function followedStores(Request $request)
    {
        $member = $request->user()->member;
        $stores = FollowedStore::where('member_id', $member->id)
            ->with(
                ['store' => function ($query) {
                    $query->select('id', 'name', 'image');
                }]
            )
            ->get();

        return response()->json([
            'member' => $member,
            'stores' => $stores,
            'message' => 'Successfully get followed stores!'
        ]);
    }

    public function updateFollowedStores(Request $request)
    {
        $follow = $request->follow;
        $store = $request->user()->store;
        if ($store && $request->store_id == $request->user()->store->id) {
            $response['status'] = 'Tidak dapat mengikuti Toko Anda sendiri!';
            throw new HttpResponseException(response()->json($response, 422));
        }

        if ($follow == 1) {
            FollowedStore::firstOrCreate(
                ['member_id' => $request->user()->member->id, 'store_id' => $request->store_id],
                ['member_id' => $request->user()->member->id, 'store_id' => $request->store_id]
            );
            return response()->json([
                'code' => 201,
                'message' => 'Successfully create Followed stores!'
            ]);
        } else if ($follow == 0) {
            try {
                FollowedStore::where('member_id', $request->user()->member->id)
                    ->where('store_id', $request->store_id)
                    ->delete();
            } catch (\Throwable $th) {
                throw new HttpResponseException(response()->json($th, 422));
            }

            return response()->json([
                'code' => 202,
                'message' => 'Successfully Unfollowed stores!'
            ]);
        } else {
            $response['status'] = 'Follow harus berisi angka 0 atau 1!';
            throw new HttpResponseException(response()->json($response, 422));
        }
    }
}
