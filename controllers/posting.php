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

use fluxbb\Controllers\Base,
	fluxbb\Models\Post,
	fluxbb\Models\Topic,
	fluxbb\Models\Forum,
	fluxbb\Models\User,
	fluxbb\Models\Config;

class FluxBB_Posting_Controller extends Base
{

	public function get_reply($tid)
	{
		$topic = Topic::where_id($tid)->first();

		if ($topic === NULL)
		{
			return Event::first('404');
		}

		return View::make("fluxbb::posting.post")
			->with('topic', $topic)
			->with('action', __('fluxbb::post.post_a_reply'));
	}

	// TODO: validation
	public function put_reply($tid)
	{
		$post_data = array(
			'poster'			=> User::current()->username,
			'poster_id'			=> User::current()->id,
			'poster_ip'			=> Request::ip(),
			'message'			=> Input::get('req_message'),
			'hide_smilies'		=> Input::get('hide_smilies') ? '1' : '0',
			'posted'			=> Request::time(),
			'topic_id'			=> $tid
		);

		if (!Auth::check())
		{
			$post_data['poster'] = Input::get('req_username');
			$post_data['poster_email'] = Config::enabled('p_force_guest_email') ? Input::get('req_email') : Input::get('email');
		}

		$post = Post::create($post_data);

		return Redirect::to_action('fluxbb::post', array($post->id))->with('message', __('fluxbb::post.post_added'));
	}

	public function get_topic($fid)
	{
		$forum = Forum::where_id($fid)->first();

		if ($forum === NULL)
		{
			return Event::first('404');
		}

		return View::make("fluxbb::posting.post")
			->with('forum', $forum)
			->with('action', __('fluxbb::forum.post_topic'));
	}

	// TODO: validation
	public function put_topic($fid)
	{
		$topic_data = array(
			'poster'			=> User::current()->username,
			'subject'			=> Input::get('req_subject'),
			'posted'			=> Request::time(),
			'last_post'			=> Request::time(),
			'last_poster'		=> User::current()->username,
			'sticky'			=> Input::get('stick_topic') ? '1' : '0',
			'forum_id'			=> $fid,
		);

		if (!Auth::check())
		{
			$topic_data['poster'] = $topic_data['last_poster'] = Input::get('req_username');
		}

		$topic = Topic::create($topic_data);

		$post_data = array(
			'poster'			=> User::current()->username,
			'poster_id'			=> User::current()->id,
			'poster_ip'			=> Request::ip(),
			'message'			=> Input::get('req_message'),
			'hide_smilies'		=> Input::get('hide_smilies') ? '1' : '0',
			'posted'			=> Request::time(),
			'topic_id'			=> $topic->id
		);

		if (!Auth::check())
		{
			$post_data['poster'] = Input::get('req_username');
			$post_data['poster_email'] = Config::enabled('p_force_guest_email') ? Input::get('req_email') : Input::get('email');
		}

		$post = Post::create($post_data);

		return Redirect::to_action('fluxbb::topic', array($topic->id))->with('message', __('fluxbb::topic.topic_added'));
	}
}
