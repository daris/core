<?php
/**
 * FluxBB - fast, light, user-friendly PHP forum software
 * Copyright (C) 2008-2012 FluxBB.org
 * based on code by Rickard Andersson copyright (C) 2002-2008 PunBB
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public license for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @category	FluxBB
 * @package		Core
 * @copyright	Copyright (c) 2008-2012 FluxBB (http://fluxbb.org)
 * @license		http://www.gnu.org/licenses/gpl.html	GNU General Public License
 */

namespace fluxbb\Models;

class Forum extends Base
{

	public function topics()
	{
		return $this->has_many('fluxbb\\Models\\Topic');
	}

	public function subscriptions()
	{
		return $this->has_many('fluxbb\\Models\\ForumSubscription');
	}

	public function subscription()
	{
		return $this->has_one('fluxbb\\Models\\ForumSubscription')
			->where_user_id(User::current()->id);
	}

	public function perms()
	{
		// TODO: has_one() with group condition?
		return $this->has_many('fluxbb\\Models\\ForumPerms')
			->where_group_id(User::current()->id)
			->where_null('read_forum')
			->or_where('read_forum', '=', '1');
	}

	public function track()
	{
		return $this->has_one('fluxbb\\Models\\ForumTrack')
			->where_user_id(User::current()->id);
	}

	public function unread_topics()
	{
		return Topic::left_join('topic_track', function ($join)
		{
			$join->on('topics.id', '=', 'topic_track.topic_id');
			$join->on('topic_track.user_id', '=', \DB::raw(User::current()->id));
		})
		->where('topics.forum_id', '=', $this->id)
		->where_null('topics.moved_to')
		->where('topics.last_post', '>', \DB::raw($this->mark_time()))
		->or_where(function ($query)
		{
			$query->where_null('topic_track.topic_id');
			$query->or_where('topic_track.mark_time', '>', 'topics.last_post');
		})
		->get();
	}

	public function num_topics()
	{
		return $this->redirect_url == '' ? $this->num_topics : '-';
	}

	public function num_posts()
	{
		return $this->redirect_url == '' ? $this->num_posts : '-';
	}

	public function is_user_subscribed()
	{
		return Auth::check() && !is_null($this->subscription);
	}

	public function moderators()
	{
		return $this->moderators != '' ? unserialize($this->moderators) : array();
	}

	public function is_moderator()
	{
		return User::current()->is_moderator() && array_key_exists(User::current()->username, $this->moderators());
	}

	public function is_admmod()
	{
		return User::current()->is_admin() || $this->is_moderator();
	}

	public function mark_time()
	{
		return !is_null($this->track) ? $this->track->mark_time : User::current()->last_mark;
	}

	public function is_unread()
	{
		return $this->last_post > $this->mark_time();
	}

	public function mark_read()
	{
		if (!is_null($this->track))
		{
			$this->track()->update(array('mark_time' => time()));
		}
		else
		{
			$this->track()->insert(array('mark_time' => time()));
		}
	}
}
