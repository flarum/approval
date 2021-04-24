<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Approval\Access;

use Flarum\Tags\Tag;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class TagPolicy extends AbstractPolicy
{
    /**
     * @param User $actor
     * @param Tag $tag
     * @return bool|null
     */
    public function addToDiscussion(User $actor, Tag $tag)
    {
        if ($tag->is_restricted && !$actor->hasPermission('tag'.$tag->id.'.discussion.startWithoutApproval')) {
            return $this->deny();
        }
    }
}
