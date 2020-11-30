<?php

namespace App\Traits;

trait Followable
{
    /**
     * Mark user as following the object
     *
     * @return Response
     */
    public function follow($id, Request $request)
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        };

        if (!$object = Entity::find($id)) {
            flash()->error('Error', 'No such entity');

            return back();
        };

        // add the following response
        $follow = new Follow;
        $follow->object_id = $id;
        $follow->user_id = $this->user->id;
        $follow->object_type = 'entity'; // 1 = Attending, 2 = Interested, 3 = Uninterested, 4 = Cannot Attend
        $follow->save();

        Log::info('User ' . $id . ' is following ' . $entity->name);

        flash()->success('Success', 'You are now following the entity - ' . $entity->name);

        return back();
    }

    /**
     * Mark user as unfollowing the entity.
     *
     * @return Response
     */
    public function unfollow($id, Request $request)
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        };

        if (!$entity = Entity::find($id)) {
            flash()->error('Error', 'No such entity');

            return back();
        };

        // delete the follow
        $response = Follow::where('object_id', '=', $id)->where('user_id', '=', $this->user->id)->where('object_type', '=', 'entity')->first();
        $response->delete();

        flash()->success('Success', 'You are no longer following the entity.');

        return back();
    }
}
