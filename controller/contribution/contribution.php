<?php
/**
*
* This file is part of the phpBB Customisation Database package.
*
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
* For full copyright and license information, please see
* the docs/CREDITS.txt file.
*
*/

namespace phpbb\titania\controller\contribution;

class contribution extends base
{
	/**
	* Delegates requested page to appropriate method.
	*
	* @param string $contrib_type	Contrib type URL identifier.
	* @param string $contrib		Contrib name clean.
	* @param string $page			Requested page.
	*
	* @return \Symfony\Component\HttpFoundation\Response
	*/
	public function base($contrib_type, $contrib, $page)
	{
		$this->load_contrib($contrib);

		$page = ($page) ?: 'details';

		if (!in_array($page, array('report', 'details', 'demo', 'queue_discussion', 'rate')))
		{
			return $this->helper->error('NO_PAGE', 404);
		}

		$this->display->assign_global_vars();
		$this->generate_navigation($page);
		$this->generate_breadcrumbs();

		return $this->{$page}();
	}

	/**
	* Report page.
	*
	* @return null
	*/
	protected function report()
	{
		// Check permissions
		if (!$this->user->data['is_registered'])
		{
			return $this->helper->needs_auth();
		}

		$this->user->add_lang_ext('phpbb/titania', 'posting');
		$this->user->add_lang('mcp');

		if (confirm_box(true))
		{
			$message = $this->request->variable('report_text', '', true);
			$notify_reporter = $this->request->variable('notify', false);
			$this->contrib->report($message, $notify_reporter);
		}
		else
		{
			$this->template->assign_var('S_CAN_NOTIFY', true);

			confirm_box(false, 'REPORT_CONTRIBUTION', '', 'posting/report_body.html');
		}

		redirect($this->contrib->get_url());
	}

	/**
	* Details page.
	*
	* @return \Symfony\Component\HttpFoundation\Response
	*/
	protected function details()
	{
		$this->contrib->get_download();
		$this->contrib->get_revisions();
		$this->contrib->get_screenshots();
		$this->contrib->get_rating();

		$this->contrib->assign_details();

		if (!$this->user->data['is_bot'])
		{
			$this->contrib->increase_view_counter();
		}

		// Set tracking
		\titania_tracking::track(TITANIA_CONTRIB, $this->contrib->contrib_id);

		// Subscriptions
		\titania_subscriptions::handle_subscriptions(TITANIA_CONTRIB, $this->contrib->contrib_id, $this->contrib->get_url(), 'SUBSCRIBE_CONTRIB');

		// Canonical URL
		$this->template->assign_var('U_CANONICAL', $this->contrib->get_url());

		return $this->helper->render(
			'contributions/contribution_details.html',
			$this->contrib->contrib_name . ' - ' . $this->user->lang['CONTRIB_DETAILS']
		);
	}

	/**
	* Styles demo page.
	*
	* @return \Symfony\Component\HttpFoundation\Response
	*/
	protected function demo()
	{
		if (!$this->contrib->contrib_demo || $this->contrib->contrib_status != TITANIA_CONTRIB_APPROVED ||
			$this->contrib->contrib_type != TITANIA_TYPE_STYLE || !$this->contrib->options['demo'])
		{
			return $this->helper->error('NO_DEMO', 404);
		}

		$demo = new \titania_styles_demo($this->contrib->contrib_id);
		$demo->load_styles();
		$demo->assign_details();

		return $this->helper->render('contributions/demo.html', $this->user->lang['CONTRIB_DEMO']);
	}

	/**
	* Queue discussion topic redirect.
	*
	* @return Returns \Symfony\Component\HttpFoundation\Response if no
	*	topic was found, otherwise redirects to topic.
	*/
	protected function queue_discussion()
	{
		$sql = 'SELECT *
			FROM ' . TITANIA_TOPICS_TABLE . '
			WHERE topic_type = ' . TITANIA_QUEUE_DISCUSSION . '
			AND parent_id = ' . $this->contrib->contrib_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($row)
		{
			$topic = new \titania_topic;
			$topic->__set_array($row);

			redirect($topic->get_url());
		}

		return $this->helper->error('NO_QUEUE_DISCUSSION_TOPIC', 404);
	}

	/**
	* Rating action.
	*
	* @return Returns \Symfony\Component\HttpFoundation\Response if error found,
	*	otherwise redirects back to details page.
	*/
	protected function rate()
	{
		$rating_value = $this->request->variable('value', -1.0);
		$rating = $this->contrib->get_rating();

		$result = ($rating_value == -1) ? $rating->delete_rating() : $rating->add_rating($rating_value);

		if ($result)
		{
			redirect($this->contrib->get_url());
		}

		return $this->helper->error('BAD_RATING');
	}
}