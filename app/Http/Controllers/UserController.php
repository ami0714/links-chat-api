<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;


class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getUser(Request $request):JsonResponse
    {
        $userId = request()->user()->id;

        $user = DB::selectOne('SELECT * FROM users WHERE id = :id',['id'=>$userId]);
        


    


        return response()->json([
            'status' => true,
            'data' => $user
            
        ]);
    }

    
    public function getOtherUser(Request $request):JsonResponse
    {
        $otherUserUid = $request->query('uid');

        $otherUser = DB::selectOne(
            'SELECT uid, username, name, avatar_path FROM users WHERE uid = :uid',
            ['uid' => $otherUserUid]
        );

        if (!$otherUser) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'dataUser' => $otherUser
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
