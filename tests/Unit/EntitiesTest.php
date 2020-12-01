<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class EntitiesTest extends TestCase
{
    protected $entity;

    public function setUp():void
    {
        parent::setUp();

        $this->entity = factory('App\Entity')->create();
    }

    /**
     * Test that entities are browsable
     *
     * @test void
     */
    public function entities_browsable()
    {
        $this->get('/entities')->assertSee('Entities');
    }

    /** @test */
    public function a_user_can_view_a_single_entity()
    {
        // create an entity
        $entity = factory('App\Entity')->create();

        // when we visit an entity page
        $this->get('/entities/' . $entity->slug)
            ->assertSee($entity->name);

        // show failing for some unknown reason
    }

    /** @test */
    public function a_user_can_edit_an_entity_they_own()
    {
        // create a user
        $user = factory('App\User')->create();

        // add an entity created by that user
        $entity = factory('App\Entity')
            ->create(['created_by' => $user->id]);

        // try to edit the entity as the user who created
        $this->actingAs($user)
            ->get('/entities/' . $entity->slug . '/edit')
            ->assertStatus(200)
            ->assertSee($entity->name);
    }

    /** @test */
    public function an_entity_has_a_creator()
    {
        // add that thread
        $entity = factory('App\Entity')->make();

        $this->assertInstanceOf('App\User', $entity->user);
    }

    /** @test */
    public function an_entity_can_add_a_photo()
    {
        // add a photo
        $photo = \App\Photo::all()->first();
        $this->entity->addPhoto($photo);

        $one = $this->entity->photos->first();

        $this->assertInstanceOf('App\Photo', $one);
    }

//    /** @test */
//    function an_authenticated_user_can_create_new_entities()
//    {
//        $this->signIn();
//
//        $entity = factory('App\Entity')->make();
//
//        $response = $this->post('/entities', $entity->toArray());
//
//        $this->get($response->headers->get('Location'))
//            ->assertSee($entity->name);
//
//    }

//    /** @test */
//    function a_user_can_filter_entities_according_to_a_tag()
//    {
//        $tag = create('App\Tag');
//        $entityWithTag = create('App\Thread', ['thread_category_id' => $category->id]);
//        $threadNotInCategory = create('App\Thread');
//
//        $this->get('/threads/category/' . $category->name)
//            ->assertSee($threadInCategory->name)
//            ->assertDontSee($threadNotInCategory->name);
//    }
//
//    /** @test */
//    function a_user_can_filter_threads_by_any_username()
//    {
//        $this->signIn(create('App\User', ['name' => 'JohnDoe']));
//
//        $threadByJohn = create('App\Thread', ['created_by' => auth()->id()]);
//        $threadNotByJohn = create('App\Thread', ['created_by' => 1]);
//        $threadNotByJohn->created_by = 1;
//        $threadNotByJohn->save();
//
//        $this->get('threads/filter?filter_user=JohnDoe')
//            ->assertSee($threadByJohn->name)
//            ->assertDontSee($threadNotByJohn->name);
//    }
}
