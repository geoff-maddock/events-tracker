<?php

namespace App\Traits;

trait Likeable
{
    /**
     * Mark user as liking the object
     *
     * @return Response
     */
    public function like($id, Request $request)
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

        // add the liking response
        $like = new Like;
        $like->object_id = $id;
        $like->user_id = $this->user->id;
        $like->object_type = 'entity';
        $like->save();

        Log::info('User ' . $id . ' is likeing ' . $entity->name);

        flash()->success('Success', 'You are now liking the entity - ' . $entity->name);

        return back();
    }

    /**
     * Mark user as unliking the entity.
     *
     * @return Response
     */
    public function unlike($id, Request $request)
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

        // delete the like
        $response = Like::where('object_id', '=', $id)->where('user_id', '=', $this->user->id)->where('object_type', '=', 'entity')->first();
        $response->delete();

        flash()->success('Success', 'You are no longer likeing the entity.');

        return back();
    }
}
