<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Approval\Listener;

use Flarum\Core\Post;
use Flarum\Event\ConfigureModelDefaultAttributes;
use Flarum\Event\PostWillBeSaved;
use Flarum\Flags\Flag;
use Flarum\Tags\Tag;
use Illuminate\Contracts\Events\Dispatcher;

class UnapproveNewContent
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureModelDefaultAttributes::class, [$this, 'approveByDefault']);
        $events->listen(PostWillBeSaved::class, [$this, 'unapproveNewPosts']);
    }

    /**
     * @param ConfigureModelDefaultAttributes $event
     */
    public function approveByDefault(ConfigureModelDefaultAttributes $event)
    {
        if ($event->isModel(Post::class)) {
            $event->attributes['is_approved'] = true;
        }
    }

    /**
     * @param PostWillBeSaved $event
     */
    public function unapproveNewPosts(PostWillBeSaved $event)
    {
        $post = $event->post;
        $unapprove = false;

        if (! $post->exists) {
            $ability = $post->discussion->number_index == 0 ? 'startWithoutApproval' : 'replyWithoutApproval';

            $disallowedTags = Tag::getIdsWhereCannot($event->actor, 'discussion.' . $ability);

            foreach ($event->data['relationships']['tags']['data'] as $value) {
                if (in_array($value['id'], $disallowedTags)) {
                    $unapprove = true;
                }
            }

            if($unapprove === false) {
                if ($event->actor->can($ability, $post->discussion)) {
                    if ($post->is_approved === null) {
                        $post->is_approved = true;
                    }

                    return;
                }
            } else {
                $post->is_approved = false;

                $post->afterSave(function ($post) {
                    if ($post->number == 1) {
                        $post->discussion->is_approved = false;
                        $post->discussion->save();
                    }

                    $flag = new Flag;

                    $flag->post_id = $post->id;
                    $flag->type = 'approval';
                    $flag->time = time();

                    $flag->save();
                });
            }
        }
    }
}
