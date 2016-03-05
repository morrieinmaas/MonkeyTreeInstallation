<?php
/**
* @package      EasySocial
* @copyright    Copyright (C) 2010 - 2015 Stack Ideas Sdn Bhd. All rights reserved.
* @license      GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined('_JEXEC') or die('Unauthorized Access');

FD::import('site:/views/views');

class EasySocialViewProjects extends EasySocialSiteView
{
    /**
     * Checks if the project feature is enabled.
     *
     * @since  1.3
     * @access public
     */
    private function checkFeature()
    {
        if (!FD::config()->get('projects.enabled')) {
            $this->setMessage(JText::_('COM_EASYSOCIAL_PROJECTS_DISABLED'), SOCIAL_MSG_ERROR);

            FD::info()->set($this->getMessage());

            $this->redirect(FRoute::dashboard(array(), false));
            $this->close();
        }
    }

    /**
     * Displays the project listing main page.
     *
     * @since  1.3
     * @access public
     */
    public function display($tpl = null)
    {
        $this->checkFeature();

        // Check for profile completeness
        FD::checkCompleteProfile();

        FD::page()->title(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS'));
        FD::page()->breadcrumb(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS'));

        // Get the user's id
        $id = $this->input->get('userid', null, 'int');

        // Load up the user that is being viewed (If filtering projects by specific user)
        $user = FD::user($id);

        // Get the filter
        $filter = $this->input->get('filter', 'all', 'string');

        $allowedFilter = array('week1', 'week2', 'all', 'featured', 'mine', 'participated', 'invited', 'going', 'pending', 'maybe', 'notgoing', 'past', 'ongoing', 'upcoming', 'date', 'nearby');

        if (!in_array($filter, $allowedFilter)) {
            return JError::raiseError(404, JText::_('COM_EASYSOCIAL_PROJECTS_INVALID_FILTER_ID'));
        }

        // Since not logged in users cannot filter by 'invited' or 'mine', they shouldn't be able to access these filters at all
        if ($this->my->guest && ($filter == 'invited' || $filter == 'mine' || $filter == 'going' || $filter == 'maybe' || $filter == 'notgoing' || $filter == 'participated')) {
            return $this->app->redirect(FRoute::dashboard(array(), false));
        }

        // Get the ordering
        $ordering = $this->input->get('ordering', 'start', 'word');

        // See if past should be included
        $includePast = $this->input->getInt('includePast', 0);

        // Load up projects model
        $model = FD::model('Projects');

        // Theme related settings
        $showSorting = true;
        $showPastFilter = true;
        $showDistance = false;
        $showDistanceSorting = false;
        $hasLocation = false;

        // Flag to see if this process should be delayed
        // Currently it is for the case of nearby filter
        // Nearby filter can only work if the location is retrieved through javascript
        $delayed = false;

        // Get the list of categories
        $projectCategoriesModel = FD::model('ProjectCategories');
        $categories = $projectCategoriesModel->getCategories(array('state' => SOCIAL_STATE_PUBLISHED, 'ordering' => 'ordering'));

        // Default options for listing
        $options = array(
            'state' => SOCIAL_STATE_PUBLISHED,
            'ordering' => $ordering,
            'type' => $this->my->isSiteAdmin() ? 'all' : 'user',
            'featured' => FD::config()->get('projects.listing.includefeatured') ? 'all' : false,
            'limit' => FD::themes()->getConfig()->get('projects_limit', 20)
        );

        // Set the route options so that filter can add extra parameters
        $routeOptions = array(
            'option' => SOCIAL_COMPONENT_NAME,
            'view' => 'projects'
        );

        // If user is an admin then he should be able to see all projects
        // If not then we set guestuid as the user id without any guest state
        // This is because project list should consist of
        // Open, Closed, and for Invite Only if the user is part of it

        // If filter by guest state, then types is always all because we only get what the user is involved

        // Determines if this request is filtering projects by specific category
        $categoryId = $this->input->get('categoryid', 0, 'int');

        $activeCategory = false;

        if ($categoryId) {

            $activeCategory = FD::table('ProjectCategory');
            $state = $activeCategory->load($categoryId);

            if (!$state) {
                return JError::raiseError(404, JText::_('COM_EASYSOCIAL_PROJECTS_INVALID_CATEGORY_ID'));
            }

            $options['category'] = $activeCategory->id;

            $filter = 'all';

            FD::page()->title($activeCategory->get('title'));

            // We do not want to include past projects here
            if (!$includePast) {
                $options['ongoing'] = true;
                $options['upcoming'] = true;
            }

            $routeOptions['categoryid'] = $activeCategory->getAlias();
        }

        if (!$activeCategory && !empty($filter)) {
            $routeOptions['filter'] = $filter;
        }

        $this->set('activeCategory', $activeCategory);

        // Process filters
        if ($filter === 'all') {
            // Need to get featured projects separately here
            $featuredOptions = array(
                'featured' => true,
                'state' => SOCIAL_STATE_PUBLISHED,
                'type' => array(
                    SOCIAL_PROJECT_TYPE_PUBLIC,
                    SOCIAL_PROJECT_TYPE_PRIVATE
               )
           );

            if ($activeCategory) {
                $featuredOptions['category'] = $activeCategory->id;
            }

            $featuredProjects = $model->getProjects($featuredOptions);

            $this->set('featuredProjects', $featuredProjects);

            // We do not want to include past projects here
            if (!$includePast) {
                $options['ongoing'] = true;
                $options['upcoming'] = true;
            }
        }

        if ($filter === 'featured') {
            FD::page()->title(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_FEATURED'));
            $options['featured'] = true;

            // We do not want to include past projects here
            if (!$includePast) {
                $options['ongoing'] = true;
                $options['upcoming'] = true;
            }
        }

        if ($filter === 'mine') {
            FD::page()->title(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_MINE'));
            $options['creator_uid'] = $this->my->id;
            $options['creator_type'] = SOCIAL_TYPE_USER;
            $options['type'] = 'all';

            // We do not want to include past projects here
            if (!$includePast) {
                $options['ongoing'] = true;
                $options['upcoming'] = true;
            }
        }

        if ($filter === 'participated') {
            FD::page()->title(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_PARTICIPATED'));

            $options['guestuid'] = $this->my->id;
            $options['type'] = 'all';

            // We do not want to include past projects here
            if (!$includePast) {
                $options['ongoing'] = true;
                $options['upcoming'] = true;
            }
        }

        if ($filter === 'invited') {
            FD::page()->title(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_INVITED'));

            $options['gueststate'] = SOCIAL_PROJECT_GUEST_INVITED;
            $options['guestuid'] = $this->my->id;
            $options['type'] = 'all';

            // We do not want to include past projects here
            if (!$includePast) {
                $options['ongoing'] = true;
                $options['upcoming'] = true;
            }
        }

        if ($filter === 'going') {
            FD::page()->title(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_GOING'));

            $options['gueststate'] = SOCIAL_PROJECT_GUEST_GOING;
            $options['guestuid'] = $this->my->id;
            $options['type'] = 'all';

            // We do not want to include past projects here
            if (!$includePast) {
                $options['ongoing'] = true;
                $options['upcoming'] = true;
            }
        }

        if ($filter === 'pending') {
            FD::page()->title(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_PENDING'));

            $options['gueststate'] = SOCIAL_PROJECT_GUEST_PENDING;
            $options['guestuid'] = $this->my->id;
            $options['type'] = 'all';

            // We do not want to include past projects here
            if (!$includePast) {
                $options['ongoing'] = true;
                $options['upcoming'] = true;
            }
        }

        if ($filter === 'maybe') {
            FD::page()->title(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_MAYBE'));

            $options['gueststate'] = SOCIAL_PROJECT_GUEST_MAYBE;
            $options['guestuid'] = $this->my->id;
            $options['type'] = 'all';

            // We do not want to include past projects here
            if (!$includePast) {
                $options['ongoing'] = true;
                $options['upcoming'] = true;
            }
        }

        if ($filter === 'notgoing') {
            FD::page()->title(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_NOTGOING'));

            $options['gueststate'] = SOCIAL_PROJECT_GUEST_NOTGOING;
            $options['guestuid'] = $this->my->id;
            $options['type'] = 'all';

            // We do not want to include past projects here
            if (!$includePast) {
                $options['ongoing'] = true;
                $options['upcoming'] = true;
            }
        }

        if ($filter === 'past') {
            FD::page()->title(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_PAST'));

            $options['start-before'] = FD::date()->toSql();
            $options['ordering'] = 'created';
            $options['direction'] = 'desc';

            $showPastFilter = false;
        }

        if ($filter === 'ongoing') {
            FD::page()->title(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_ONGOING'));

            $options['ongoing'] = true;

            $showPastFilter = false;
        }

        if ($filter === 'upcoming') {
            FD::page()->title(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_UPCOMING'));

            $options['upcoming'] = true;

            $showPastFilter = false;
        }

        if ($filter === 'date') {
            $now = FD::date();

            list($nowYMD, $nowHMS) = explode(' ', $now->toSql(true));

            $input = $this->input->getString('date');

            // We need segments to be populated. If no input is passed, then it is today, and we use today as YMD then
            if (empty($input)) {
                $input = $nowYMD;
            }

            if (!empty($input)) {
                $routeOptions['date'] = $input;
            }


            $segments = explode('-', $input);

            $isToday = false;
            $isTomorrow = false;
            $isCurrentMonth = false;
            $isCurrentYear = false;

            $start = $nowYMD;
            $end = $nowYMD;

            $nextLink = '';
            $prevLink = '';
            $nextDate = '';
            $prevDate = '';
            $nextTitle = '';
            $prevTitle = '';

            // Depending on the amount of segments
            // 1 = filter by year
            // 2 = filter by month
            // 3 = filter by day

            $mode = count($segments);

            switch ($mode) {
                case 1:
                    $start = $segments[0] . '-01-01';
                    $end = $segments[0] . '-12-31';

                    $dateFormat = 'COM_EASYSOCIAL_DATE_Y';

                    $currentYear = $now->format('Y', true);

                    $isCurrentYear = $input == $currentYear;

                    $nextDate = $segments[0] + 1;
                    $prevDate = $segments[0] - 1;

                    $nextLink = FRoute::projects(array('filter' => 'date', 'date' => $nextDate));
                    $prevLink = FRoute::projects(array('filter' => 'date', 'date' => $prevDate));

                    if ($nextDate == $currentYear) {
                        $nextTitle = JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_YEAR');
                    } else {
                        $nextTitle = JText::sprintf('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_DATE', FD::date($nextDate . '-01-01')->format(JText::_($dateFormat), true));
                    }

                    if ($prevDate == $currentYear) {
                        $prevTitle = JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_YEAR');
                    } else {
                        $prevTitle = JText::sprintf('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_DATE', FD::date($prevDate . '-01-01')->format(JText::_($dateFormat), true));
                    }
                break;

                case 2:
                    $start = $segments[0] . '-' . $segments[1] . '-01';

                    // Need to get the month's maximum day
                    $monthDate = FD::date($start);
                    $maxDay = $monthDate->format('t');

                    $end = $segments[0] . '-' . $segments[1] . '-' . str_pad($maxDay, 2, '0', STR_PAD_LEFT);

                    $dateFormat = 'COM_EASYSOCIAL_DATE_MY';

                    $currentMonth = $now->format('Y-m', true);

                    $isCurrentMonth = $input == $currentMonth;

                    $nextDate = FD::date($start, false)->modify('+1 month')->format('Y-m');
                    $prevDate = FD::date($start, false)->modify('-1 month')->format('Y-m');

                    $nextLink = FRoute::projects(array('filter' => 'date', 'date' => $nextDate));
                    $prevLink = FRoute::projects(array('filter' => 'date', 'date' => $prevDate));

                    if ($nextDate == $currentMonth) {
                        $nextTitle = JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_MONTH');
                    } else {
                        $nextTitle = JText::sprintf('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_DATE', FD::date($nextDate . '-01', false)->format(JText::_($dateFormat)));
                    }

                    if ($prevDate == $currentMonth) {
                        $prevTitle = JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_MONTH');
                    } else {
                        $prevTitle = JText::sprintf('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_DATE', FD::date($prevDate . '-01', false)->format(JText::_($dateFormat)));
                    }
                break;

                default:
                case 3:
                    $start = $segments[0] . '-' . $segments[1] . '-' . $segments[2];
                    $end = $segments[0] . '-' . $segments[1] . '-' . $segments[2];

                    $dateFormat = 'COM_EASYSOCIAL_DATE_DMY';

                    $currentDay = $now->format('Y-m-d', true);

                    $nextDay = $now->modify('+1 day')->format('Y-m-d', true);

                    $isToday = $input == $currentDay;

                    $isTomorrow = $input == $nextDay;

                    $nextDate = FD::date($start, false)->modify('+1 day')->format('Y-m-d');
                    $prevDate = FD::date($start, false)->modify('-1 day')->format('Y-m-d');

                    $nextLinkOptions = array('filter' => 'date');
                    $prevLinkOptions = array('filter' => 'date');

                    if ($nextDate == $currentDay) {
                        $nextTitle = JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_TODAY') . ' - ' . FD::date($nextDate, false)->format(JText::_($dateFormat));
                    } elseif ($nextDate == $nextDay) {
                        $nextTitle = JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_TOMORROW') . ' - ' . FD::date($nextDate)->format(JText::_($dateFormat));

                        $nextLinkOptions['date'] = $nextDate;
                    } else {
                        $nextTitle = JText::sprintf('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_DATE', FD::date($nextDate, false)->format(JText::_($dateFormat)));

                        $nextLinkOptions['date'] = $nextDate;
                    }

                    $nextLink = FRoute::projects($nextLinkOptions);

                    if ($prevDate == $currentDay) {
                        $prevTitle = JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_TODAY') . ' - ' . FD::date($prevDate, false)->format(JText::_($dateFormat));
                    } elseif ($prevDate == $nextDay) {
                        $prevTitle = JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_TOMORROW') . ' - ' . FD::date($prevDate, false)->format(JText::_($dateFormat));

                        $prevLinkOptions['date'] = $prevDate;
                    } else {
                        $prevTitle = JText::sprintf('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_DATE', FD::date($prevDate, false)->format(JText::_($dateFormat)));

                        $prevLinkOptions['date'] = $prevDate;
                    }

                    $prevLink = FRoute::projects($prevLinkOptions);

                break;
            }

            FD::page()->title(JText::sprintf('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_DATE', FD::date($start)->format(JText::_($dateFormat), true)));

            if ($isToday) {
                FD::page()->title(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_TODAY') . ' - ' . FD::date($start)->format(JText::_($dateFormat), true));
            }

            if ($isTomorrow) {
                FD::page()->title(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_TOMORROW') . ' - ' . FD::date($start)->format(JText::_($dateFormat), true));
            }

            if ($isCurrentMonth) {
                FD::page()->title(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_MONTH'));
            }

            if ($isCurrentYear) {
                FD::page()->title(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_YEAR'));
            }

            $this->set('dateFormat', $dateFormat);

            $options['start-after'] = $start . ' 00:00:00';
            $options['start-before'] = $end . ' 23:59:59';

            $this->set('isToday', $isToday);
            $this->set('isTomorrow', $isTomorrow);
            $this->set('isCurrentMonth', $isCurrentMonth);
            $this->set('isCurrentYear', $isCurrentYear);
            $this->set('nextLink', $nextLink);
            $this->set('prevLink', $prevLink);
            $this->set('nextDate', $nextDate);
            $this->set('prevDate', $prevDate);
            $this->set('nextTitle', $nextTitle);
            $this->set('prevTitle', $prevTitle);

            $this->set('date', FD::date($start, false));

            $showSorting = false;
            $showPastFilter = false;
        }

        if ($filter === 'week1') {
            FD::page()->title(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_UPCOMING_1WEEK'));

            $now = FD::date();
            $week1 = FD::date($now->toUnix() + 60*60*24*7);

            $options['start-after'] = $now->toSql();
            $options['start-before'] = $week1->toSql();

            $showPastFilter = false;
        }

        if ($filter === 'week2') {
            FD::page()->title(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_UPCOMING_2WEEK'));

            $now = FD::date();
            $week2 = FD::date($now->toUnix() + 60*60*24*14);

            $options['start-after'] = $now->toSql();
            $options['start-before'] = $week2->toSql();

            $showPastFilter = false;
        }

        // Check if there is any location data
        $userLocation = JFactory::getSession()->get('projects.userlocation', array(), SOCIAL_SESSION_NAMESPACE);
        $this->set('userLocation', $userLocation);

        if ($filter === 'nearby') {
            FD::page()->title(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS_FILTER_NEARBY'));

            $hasLocation = !empty($userLocation) && !empty($userLocation['latitude']) && !empty($userLocation['longitude']);

            // If there is no location, then we need to delay the project retrieval process
            $delayed = empty($hasLocation);

            $showSorting = false;

            $distance = $this->input->getString('distance');

            if (!empty($distance) && $distance != 10) {
                $routeOptions['distance'] = $distance;
            } else {
                $distance = 10;
            }

            $this->set('distance', $distance);

            if ($hasLocation) {
                $options['location'] = true;
                $options['distance'] = $distance;
                $options['latitude'] = $userLocation['latitude'];
                $options['longitude'] = $userLocation['longitude'];
                $options['range'] = '<=';

                // We do not want to include past projects here
                !$includePast && $options['start-after'] = FD::date()->toSql(true);

                $showDistance = true;

                $showDistanceSorting = true;
            }

            $this->set('distanceUnit', FD::config()->get('general.location.proximity.unit'));
        }

        $projects = array();

        // Get a list of projects if this is not delayed?
        if (!$delayed) {
            $projects = $model->getProjects($options);
        }

        // Get the pagination
        $pagination = $model->getPagination();

        $pagination->setVar('filter', $filter);

        if ($includePast) {
            $pagination->setVar('includePast', $includePast);
        }

        if ($ordering != 'start') {
            $pagination->setVar('ordering', $ordering);
        }

        if ($activeCategory) {
            $pagination->setVar('categoryid', $activeCategory->id);
        }

        $dateInput = $this->input->getString('date');

        if (!empty($dateInput)) {
            $pagination->setVar('date', $dateInput);
        }

        $distanceInput = $this->input->getString('distance');

        if (!empty($distanceInput)) {
            $pagination->setVar('distance', $distanceInput);
        }

        $total = $model->getTotalProjects(array('state' => SOCIAL_STATE_PUBLISHED, 'type' => $this->my->isSiteAdmin() ? 'all' : 'user', 'ongoing' => true, 'upcoming' => true));
        $totalFeaturedProjects = $model->getTotalProjects(array('state' => SOCIAL_STATE_PUBLISHED,'featured' => true, 'ongoing' => true, 'upcoming' => true));
        $totalCreatedProjects = $model->getTotalProjects(array('state' => SOCIAL_STATE_PUBLISHED, 'creator_uid' => $this->my->id, 'creator_type' => SOCIAL_TYPE_USER, 'type' => 'all', 'ongoing' => true, 'upcoming' => true));
        $totalInvitedProjects = $model->getTotalProjects(array('state' => SOCIAL_STATE_PUBLISHED, 'guestuid' => $this->my->id, 'gueststate' => SOCIAL_PROJECT_GUEST_INVITED, 'type' => 'all', 'ongoing' => true, 'upcoming' => true));

        $now = FD::date();
        $week1 = FD::date($now->toUnix() + 60*60*24*7);
        $week2 = FD::date($now->toUnix() + 60*60*24*14);

        $totalWeek1Projects = $model->getTotalProjects(array('state' => SOCIAL_STATE_PUBLISHED, 'type' => $this->my->isSiteAdmin() ? 'all' : 'user', 'start-after' => $now->toSql(), 'start-before' => $week1->toSql()));
        $totalWeek2Projects = $model->getTotalProjects(array('state' => SOCIAL_STATE_PUBLISHED, 'type' => $this->my->isSiteAdmin() ? 'all' : 'user', 'start-after' => $now->toSql(), 'start-before' => $week2->toSql()));

        $totalPastProjects = $model->getTotalProjects(array('state' => SOCIAL_STATE_PUBLISHED, 'type' => $this->my->isSiteAdmin() ? 'all' : 'user', 'start-before' => $now->toSql()));

        $totalTodayProjects = $model->getTotalProjects(array('state' => SOCIAL_STATE_PUBLISHED, 'type' => $this->my->isSiteAdmin() ? 'all' : 'user', 'start-after' => $now->format('Y-m-d 00:00:00', true), 'start-before' => $now->format('Y-m-d 23:59:59', true)));

        $tomorrow = FD::date()->modify('+1 day');

        $this->set('tomorrow', $tomorrow->format('Y-m-d', true));

        $totalTomorrowProjects = $model->getTotalProjects(array('state' => SOCIAL_STATE_PUBLISHED, 'type' => $this->my->isSiteAdmin() ? 'all' : 'user', 'start-after' => $tomorrow->format('Y-m-d 00:00:00', true), 'start-before' => $tomorrow->format('Y-m-d 23:59:59', true)));

        $currentMonth = $now->format('m', true);
        $currentYear = $now->format('Y', true);

        $this->set('currentMonth', $currentMonth);
        $this->set('currentYear', $currentYear);

        $currentMonthMaxDay = $now->format('t', true);

        $totalMonthProjects = $model->getTotalProjects(array('state' => SOCIAL_STATE_PUBLISHED, 'type' => $this->my->isSiteAdmin() ? 'all' : 'user', 'start-after' => $now->format('Y-m-01 00:00:00', true), 'start-before' => $now->format('Y-m-' . $currentMonthMaxDay . ' 23:59:59', true)));
        $totalYearProjects = $model->getTotalProjects(array('state' => SOCIAL_STATE_PUBLISHED, 'type' => $this->my->isSiteAdmin() ? 'all' : 'user', 'start-after' => $now->format('Y-01-01 00:00:00', true), 'start-before' => $now->format('Y-12-31 23:59:59', true)));

        $this->set('totalProjects', $total);
        $this->set('totalFeaturedProjects', $totalFeaturedProjects);
        $this->set('totalCreatedProjects', $totalCreatedProjects);
        $this->set('totalInvitedProjects', $totalInvitedProjects);
        $this->set('totalWeek1Projects', $totalWeek1Projects);
        $this->set('totalWeek2Projects', $totalWeek2Projects);
        $this->set('totalPastProjects', $totalPastProjects);
        $this->set('totalTodayProjects', $totalTodayProjects);
        $this->set('totalTomorrowProjects', $totalTomorrowProjects);
        $this->set('totalMonthProjects', $totalMonthProjects);
        $this->set('totalYearProjects', $totalYearProjects);
        $this->set('pagination', $pagination);
        $this->set('projects', $projects);
        $this->set('filter', $filter);
        $this->set('categories', $categories);
        $this->set('user', $user);
        $this->set('ordering', $ordering);

        $this->set('now', FD::date()->toSql());

        $hrefs = array();

        // We use start as key because order is always start by default, and it is the page default link
        $hrefs['start'] = array(
            'nopast' => FRoute::projects($routeOptions)
        );

        if (!$delayed) {

            if ($showSorting) {
                // Only need to create the "order by created" link.
                $hrefs['created'] = array(
                    'nopast' => FRoute::projects(array_merge($routeOptions, array('ordering' => 'created')))
                );
            }

                // Only need to create the "order by distance" link.
            if ($showDistanceSorting) {
                $hrefs['distance'] = array(
                    'nopast' => FRoute::projects(array_merge($routeOptions, array('ordering' => 'distance')))
                );
            }

            if ($showPastFilter) {
                // If past filter is displayed on the page, then we need to generate the past links counter part
                $hrefs['start']['past'] = FRoute::projects(array_merge($routeOptions, array('includePast' => 1)));

                if ($showSorting) {
                    // Only need to create the "order by created" link.
                    $hrefs['created']['past'] = FRoute::projects(array_merge($routeOptions, array('ordering' => 'created', 'includePast' => 1)));
                }

                if ($showDistanceSorting) {
                    // Only need to create the "order by distance" link.
                    $hrefs['distance']['past'] = FRoute::projects(array_merge($routeOptions, array('ordering' => 'distance', 'includePast' => 1)));
                }
            }
        }

        $this->set('hrefs', $hrefs);

        // Get the Guest App to generate the guest listing link for each project
        $guestApp = FD::table('App');
        $guestApp->load(array('type' => SOCIAL_TYPE_APPS, 'group' => SOCIAL_TYPE_PROJECT, 'element' => 'guests'));

        $this->set('guestApp', $guestApp);

        // Theme settings
        $this->set('showSorting', $showSorting);
        $this->set('showPastFilter', $showPastFilter);
        $this->set('showDistance', $showDistance);
        $this->set('showDistanceSorting', $showDistanceSorting);
        $this->set('hasLocation', $hasLocation);

        $this->set('includePast', $includePast);

        $this->set('delayed', $delayed);

        parent::display('site/projects/default');
    }

    /**
     * Displays the category selection for creating an project.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     */
    public function create()
    {
        // Check if projects is enabled.
        $this->checkFeature();

        // Ensure that the user is logged in
        FD::requireLogin();

        // Check for user's profile completeness
        FD::checkCompleteProfile();

        // Ensure that the user's acl is allowed to create projects
        if (!$this->my->isSiteAdmin() && !$this->my->getAccess()->get('projects.create')) {
            $this->setMessage(JText::_('COM_EASYSOCIAL_PROJECTS_NOT_ALLOWED_TO_CREATE_PROJECT'), SOCIAL_MSG_ERROR);

            $this->info->set($this->getMessage());

            return $this->redirect(FRoute::dashboard(array(), false));
        }

        // Ensure that the user did not exceed the number of allowed projects
        if (!$this->my->isSiteAdmin() && $this->my->getAccess()->intervalExceeded('projects.limit', $this->my->id)) {
            $this->setMessage(JText::_('COM_EASYSOCIAL_PROJECTS_EXCEEDED_CREATE_PROJECT_LIMIT'), SOCIAL_MSG_ERROR);

            $this->info->set($this->getMessage());

            return $this->redirect(FRoute::projects(array(), false));
        }

        // Support group projects
        $groupId = $this->input->getInt('group_id', 0);

        if (!empty($groupId)) {
            $group = FD::group($groupId);

            if (!$group->canCreateProject()) {
                $this->info->set(false, JText::_('COM_EASYSOCIAL_GROUPS_PROJECTS_NO_PERMISSION_TO_CREATE_PROJECT'), SOCIAL_MSG_ERROR);

                return $this->redirect($group->getPermalink());
            }
        }

        // Detect for an existing create project session.
        $session = JFactory::getSession();

        // Load up necessary model and tables.
        $stepSession = FD::table('StepSession');

        // If user doesn't have a record in stepSession yet, we need to create this.
        if (!$stepSession->load($session->getId())) {
            $stepSession->set('session_id', $session->getId());
            $stepSession->set('created', FD::get('Date')->toMySQL());
            $stepSession->set('type', SOCIAL_TYPE_PROJECT);

            if (!$stepSession->store()) {
                $this->setError($stepSession->getError());
                return false;
            }
        }

        FD::page()->title(JText::_('COM_EASYSOCIAL_PAGE_TITLE_SELECT_PROJECT_CATEGORY'));
        FD::page()->breadcrumb(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS') , FRoute::projects());
        FD::page()->breadcrumb(JText::_('COM_EASYSOCIAL_PAGE_TITLE_SELECT_PROJECT_CATEGORY'));

        $categoryRouteBaseOptions = array('controller' => 'projects' , 'task' => 'selectCategory');

        if (!empty($groupId)) {
            $categoryRouteBaseOptions['group_id'] = $groupId;

            $this->set('group', $group);
        }

        $this->set('categoryRouteBaseOptions', $categoryRouteBaseOptions);

        // Get the list of categories
        $model = FD::model('ProjectCategories');
        $categories = $model->getCreatableCategories($this->my->getProfile()->id);

        if (count($categories) == 1) {

            $category = $categories[0];

            // Store the category id into the session.
            $session->set('category_id', $category->id, SOCIAL_SESSION_NAMESPACE);

            // Set the current category id.
            $stepSession->uid   = $category->id;

            // When user accesses this page, the following will be the first page
            $stepSession->step  = 1;

            // Add the first step into the accessible list.
            $stepSession->addStepAccess(1);

            // Let's save this into a temporary table to avoid missing data.
            $stepSession->store();

            $this->steps();
            return;
        }

        $this->set('categories', $categories);

        parent::display('site/projects/create');
    }

    /**
     * Post action after selecting a category for creation to redirect to steps.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     */
    public function selectCategory()
    {
        $this->info->set($this->getMessage());

        if ($this->hasErrors()) {

            // Support for group projects
            // If there is a group id, we redirect back to the group instead
            $groupId = $this->input->getInt('group_id');
            if (!empty($groupId)) {
                $group = FD::group($groupId);

                return $this->redirect($group->getPermalink());
            }

            return $this->redirect(FRoute::projects(array(), false));
        }

        $url = FRoute::projects(array('layout' => 'steps', 'step' => 1), false);

        return $this->redirect($url);
    }

    /**
     * Displays the project creation steps.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     */
    public function steps()
    {
        // Require user to be logged in
        FD::requireLogin();

        // Check for profile completeness
        FD::checkCompleteProfile();

        if (!$this->my->isSiteAdmin() && !$this->my->getAccess()->get('projects.create')) {
            $this->setMessage(JText::_('COM_EASYSOCIAL_PROJECTS_NOT_ALLOWED_TO_CREATE_PROJECT'), SOCIAL_MSG_ERROR);

            $this->info->set($this->getMessage());

            return $this->redirect(FRoute::dashboard());
        }

        if (!$this->my->isSiteAdmin() && $this->my->getAccess()->intervalExceeded('projects.limit', $this->my->id)) {
            $this->setMessage(JText::_('COM_EASYSOCIAL_PROJECTS_EXCEEDED_CREATE_PROJECT_LIMIT'), SOCIAL_MSG_ERROR);

            $this->info->set($this->getMessage());

            return $this->redirect(FRoute::projects());
        }

        $session = JFactory::getSession();

        $stepSession = FD::table('StepSession');
        $stepSession->load(array('session_id' => $session->getId(), 'type' => SOCIAL_TYPE_PROJECT));

        if (empty($stepSession->step)) {
            FD::info()->set(false, 'COM_EASYSOCIAL_PROJECTS_UNABLE_TO_DETECT_CREATION_SESSION', SOCIAL_MSG_ERROR);

            return $this->redirect(FRoute::projects());
        }

        $categoryId = $stepSession->uid;

        $category = FD::table('ProjectCategory');
        $category->load($categoryId);

        if (!$category->hasAccess('create', $this->my->getProfile()->id)) {
            FD::info()->set(false, JText::_('COM_EASYSOCIAL_PROJECTS_NOT_ALLOWED_TO_CREATE_PROJECT'), SOCIAL_MSG_ERROR);

            return $this->redirect(FRoute::projects());
        }

        // Get the step
        $stepIndex = $this->input->get('step', 1, 'int');

        $sequence = $category->getSequenceFromIndex($stepIndex , SOCIAL_PROJECT_VIEW_REGISTRATION);

        if (empty($sequence)) {
            FD::info()->set(false, JText::_('COM_EASYSOCIAL_PROJECTS_NO_VALID_CREATION_STEP'), SOCIAL_MSG_ERROR);

            return $this->redirect(FRoute::projects(array('layout' => 'create')));
        }

        // We only check if step index is not 1
        if ($stepIndex > 1 && !$stepSession->hasStepAccess($stepIndex)) {
            FD::info()->set(false, JText::_('COM_EASYSOCIAL_PROJECTS_PLEASE_COMPLETE_PREVIOUS_STEP_FIRST'), SOCIAL_MSG_ERROR);

            return $this->redirect(FRoute::projects(array('layout' => 'steps', 'step' => 1)));
        }

        if (!$category->isValidStep($sequence, SOCIAL_PROJECT_VIEW_REGISTRATION)) {

            FD::info()->set(false, JText::_('COM_EASYSOCIAL_PROJECTS_INVALID_CREATION_STEP'), SOCIAL_MSG_ERROR);

            return $this->redirect(FRoute::projects(array('layout' => 'steps', 'step' => 1)));
        }

        $stepSession->set('step', $stepIndex);
        $stepSession->store();

        $reg = FD::registry();
        $reg->load($stepSession->values);

        // Support for group projects
        $groupId = $reg->get('group_id');

        if (!empty($groupId)) {
            $group = FD::group($groupId);

            if (!$group->canCreateProject()) {
                $this->info->set(false, JText::_('COM_EASYSOCIAL_GROUPS_PROJECTS_NO_PERMISSION_TO_CREATE_PROJECT'), SOCIAL_MSG_ERROR);

                return $this->redirect($group->getPermalink());
            }

            $this->set('group', $group);
        }

        $step = FD::table('FieldStep');
        $step->loadBySequence($category->id, SOCIAL_TYPE_CLUSTERS, $sequence);

        $totalSteps = $category->getTotalSteps();

        $errors = $stepSession->getErrors();

        $data = $stepSession->getValues();

        $args = array(&$data, &$stepSession, &$category);

        $fields = FD::fields();

        // Enforce privacy option to be false for projects
        $fields->init(array('privacy' => false));

        $fieldsModel = FD::model('Fields');

        $customFields = $fieldsModel->getCustomFields(array('step_id' => $step->id, 'visible' => SOCIAL_PROJECT_VIEW_REGISTRATION));

        $callback = array($fields->getHandler(), 'getOutput');

        if (!empty($customFields)) {
            $fields->trigger('onRegister', SOCIAL_FIELDS_GROUP_PROJECT, $customFields, $args, $callback);
        }

        $steps = $category->getSteps(SOCIAL_PROJECT_VIEW_REGISTRATION);

        $totalSteps = $category->getTotalSteps(SOCIAL_PROJECT_VIEW_REGISTRATION);

        // Set the breadcrumbs and page title
        FD::page()->breadcrumb(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS'), FRoute::projects());
        if (empty($groupId)) {
            FD::page()->breadcrumb(JText::_('COM_EASYSOCIAL_PAGE_TITLE_SELECT_PROJECT_CATEGORY'), FRoute::projects(array('layout' => 'create')));
        } else {
            FD::page()->breadcrumb(JText::_('COM_EASYSOCIAL_PAGE_TITLE_SELECT_PROJECT_CATEGORY'), FRoute::projects(array('layout' => 'create', 'group_id' => $groupId)));
        }
        FD::page()->breadcrumb($step->get('title'));
        FD::page()->title($step->get('title'));

        $this->set('stepSession', $stepSession);
        $this->set('steps', $steps);
        $this->set('currentStep', $sequence);
        $this->set('currentIndex', $stepIndex);
        $this->set('totalSteps', $totalSteps);
        $this->set('step', $step);
        $this->set('fields', $customFields);
        $this->set('errors', $errors);
        $this->set('category', $category);

        parent::display('site/projects/create.steps');
    }

    /**
     * Post action for saving a step during project creation to redirect either to the next step or the complete page.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     * @param  SocialTableStepSession    $stepSession The step session.
     */
    public function saveStep($stepSession = null)
    {
        // Set any messages
        $this->info->set($this->getMessage());

        if ($this->hasErrors()) {
            if (!empty($stepSession)) {
                return $this->redirect(FRoute::projects(array('layout' => 'steps', 'step' => $stepSession->step), false));
            } else {
                return $this->redirect(FRoute::projects(array('layout' => 'steps', 'step' => 1), false));
            }
        }

        return $this->redirect(FRoute::projects(array('layout' => 'steps', 'step' => $stepSession->step), false));
    }

    /**
     * Post action after completing an project creation to redirect either to the project listing for the project item.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     * @param  SocialProject    $project The SocialProject object.
     */
    public function complete($project)
    {
        // Recurring support
        // If no recurring data, then just redirect accordingly.
        // If project is in pending, then also redirect accordingly.
        if (empty($project->recurringData) || $project->isPending()) {
            $this->info->set($this->getMessage());

            if ($project->isPublished()) {
                return $this->redirect(FRoute::projects(array('layout' => 'item', 'id' => $project->getAlias()), false));
            }

            return $this->redirect(FRoute::projects(array(), false));
        }

        // If has recurring data, then we need to show the complete page to create all the necessary recurring projects

        FD::page()->breadcrumb(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS'), FRoute::projects());
        FD::page()->breadcrumb($project->getName(), $project->getPermalink());
        FD::page()->breadcrumb(JText::_('COM_EASYSOCIAL_PAGE_TITLE_CREATE_RECURRING_PROJECT'));

        FD::page()->title(JText::_('COM_EASYSOCIAL_PAGE_TITLE_CREATE_RECURRING_PROJECT'));

        // Get the recurring schedule
        $schedule = FD::model('Projects')->getRecurringSchedule(array(
            'projectStart' => $project->getProjectStart(),
            'end' => $project->recurringData->end,
            'type' => $project->recurringData->type,
            'daily' => $project->recurringData->daily
        ));

        $this->set('schedule', $schedule);

        $this->set('project', $project);

        echo parent::display('site/projects/createRecurring');
    }

    /**
     * Displays the edit project page.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     * @param  array    $errors Array of errors.
     */
    public function edit($errors = null)
    {
        FD::requireLogin();

        FD::checkCompleteProfile();

        $info = FD::info();

        if (!empty($errors)) {
            $info->set($this->getMessage());
        }

        $my = FD::user();

        $projectid = JRequest::getInt('id');

        $project = FD::project($projectid);

        if (empty($project) || empty($project->id)) {
            $info->set(false, JText::_('COM_EASYSOCIAL_PROJECTS_INVALID_PROJECT_ID'), SOCIAL_MSG_ERROR);
            return $this->redirect(FRoute::projects());
        }

        $guest = $project->getGuest($my->id);

        if (!$guest->isOwner() && !$guest->isAdmin() && !$my->isSiteAdmin()) {
            $info->set(false, JText::_('COM_EASYSOCIAL_PROJECTS_NOT_ALLOWED_TO_EDIT_PROJECT'), SOCIAL_MSG_ERROR);

            return $this->redirect(FRoute::projects());
        }

        FD::language()->loadAdmin();

        FD::page()->breadcrumb(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS'), FRoute::projects());
        FD::page()->breadcrumb($project->getName(), $project->getPermalink());
        FD::page()->breadcrumb(JText::_('COM_EASYSOCIAL_PAGE_TITLE_EDIT_PROJECT'));

        FD::page()->title(JText::sprintf('COM_EASYSOCIAL_PAGE_TITLE_EDIT_PROJECT_TITLE', $project->getName()));

        $category = FD::table('ProjectCategory');
        $category->load($project->category_id);

        $stepsModel = FD::model('Steps');
        $steps = $stepsModel->getSteps($category->id, SOCIAL_TYPE_CLUSTERS, SOCIAL_PROJECT_VIEW_EDIT);

        $fieldsModel = FD::model('Fields');

        $fieldsLib = FD::fields();

        // Enforce privacy to be false for projects
        $fieldsLib->init(array('privacy' => false));

        $callback = array($fieldsLib->getHandler(), 'getOutput');

        foreach ($steps as &$step) {
            $step->fields = $fieldsModel->getCustomFields(array('step_id' => $step->id, 'data' => true, 'dataId' => $project->id, 'dataType' => SOCIAL_TYPE_PROJECT, 'visible' => SOCIAL_PROJECT_VIEW_EDIT));

            if (!empty($step->fields)) {
                $post = JRequest::get('POST');
                $args = array(&$post, &$project, $errors);
                $fieldsLib->trigger('onEdit', SOCIAL_TYPE_PROJECT, $step->fields, $args, $callback);
            }
        }

        $this->set('project', $project);
        $this->set('steps', $steps);

        echo parent::display('site/projects/edit');
    }

    /**
     * Post action after updating an project to redirect to appropriately.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     * @param  SocialProject  $project  The SocialProject object.
     */
    public function update($project = null)
    {
        // Recurring support
        // If applies to all, we need to show a "progress update" page to update all childs through ajax.
        $applyAll = !empty($project) && $project->hasRecurringProjects() && $this->input->getInt('applyRecurring');

        // Check if need to create recurring project
        $createRecurring = !empty($project->recurringData);

        // If no apply, and no recurring create, then redirect accordingly.
        if (!$applyAll && !$createRecurring) {
            FD::info()->set($this->getMessage());

            if ($this->hasErrors() || empty($project)) {
                return $this->redirect(FRoute::projects());
            }

            return $this->redirect($project->getPermalink(false));
        }

        FD::page()->breadcrumb(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS'), FRoute::projects());
        FD::page()->breadcrumb($project->getName(), $project->getPermalink());
        FD::page()->breadcrumb(JText::_('COM_EASYSOCIAL_PAGE_TITLE_EDIT_PROJECT'));

        FD::page()->title(JText::sprintf('COM_EASYSOCIAL_PAGE_TITLE_EDIT_PROJECT_TITLE', $project->getName()));

        $post = JRequest::get('POST');

        $json = FD::json();
        $data = array();

        $disallowed = array(FD::token(), 'option', 'task', 'controller');

        foreach ($post as $key => $value) {
            if (in_array($key, $disallowed)) {
                continue;
            }

            if (is_array($value)) {
                $value = $json->encode($value);
            }

            $data[$key] = $value;
        }

        $string = $json->encode($data);

        $this->set('data', $string);
        $this->set('project', $project);

        $updateids = array();

        if ($applyAll) {
            $children = $project->getRecurringProjects();

            foreach ($children as $child) {
                $updateids[] = $child->id;
            }
        }

        $this->set('updateids', $json->encode($updateids));

        $schedule = array();

        if ($createRecurring) {
            // If there is recurring data, then we back up the post values and the recurring data in the the project params
            $clusterTable = FD::table('Cluster');
            $clusterTable->load($project->id);
            $projectParams = FD::makeObject($clusterTable->params);
            $projectParams->postdata = $data;
            $projectParams->recurringData = $project->recurringData;
            $clusterTable->params = FD::json()->encode($projectParams);
            $clusterTable->store();

            // Get the recurring schedule
            $schedule = FD::model('Projects')->getRecurringSchedule(array(
                'projectStart' => $project->getProjectStart(),
                'end' => $project->recurringData->end,
                'type' => $project->recurringData->type,
                'daily' => $project->recurringData->daily
            ));
        }

        $this->set('schedule', $json->encode($schedule));

        echo parent::display('site/projects/update');
    }

    /**
     * Post process after the project avatar is removed
     *
     * @since   1.3
     * @access  public
     * @param   SocialProject     The project object
     */
    public function removeAvatar(SocialProject $project)
    {
        FD::info()->set($this->getMessage());

        $permalink = $project->getPermalink(false);

        $this->redirect($permalink);
    }

    /**
     * Post process after a user is deleted from the group
     *
     * @since   1.2
     * @access  public
     * @param   SocialGroup
     */
    public function removeGuest($project)
    {
        $this->info->set($this->getMessage());

        return $this->redirect(FRoute::projects(array('layout' => 'item', 'id' => $project->getAlias()), false));
    }

    /**
     * Displays the project item page.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     */
    public function item()
    {
        // Check if projects is enabled.
        $this->checkFeature();

        // Check for profile completeness
        FD::checkCompleteProfile();

        // Get the project id
        $id = $this->input->get('id', 0, 'int');

        // Load up the project
        $project = FD::project($id);

        // Set the default redirect url
        $defaultRedirect = FRoute::projects(array(), false);

        if (empty($project) || empty($project->id)) {
            return JError::raiseError(404, JText::_('COM_EASYSOCIAL_PROJECTS_INVALID_PROJECT_ID'));
        }

        if (!$project->isPublished()) {
            FD::info()->set(false, JText::_('COM_EASYSOCIAL_PROJECTS_PROJECT_UNAVAILABLE'), SOCIAL_MSG_ERROR);

            return $this->redirect($defaultRedirect);
        }

        // Determines if the current user is a guest of this project
        $guest = $project->getGuest($this->my->id);

        // Support for group project
        // If user is not a group member, then redirect to group page
        if ($project->isGroupProject()) {
            $group = FD::group($project->getMeta('group_id'));

            if (!$this->my->isSiteAdmin() && !$project->isOpen() && !$group->isMember()) {
                FD::info()->set(false, JText::_('COM_EASYSOCIAL_GROUPS_PROJECTS_NO_PERMISSION_TO_VIEW_PROJECT'), SOCIAL_MSG_ERROR);

                return $this->redirect($group->getPermalink());
            }

            $this->set('group', $group);
        } else {

            if (!$this->my->isSiteAdmin() && $project->isInviteOnly() && !$guest->isParticipant()) {
                FD::info()->set(false, JText::_('COM_EASYSOCIAL_PROJECTS_NO_ACCESS_TO_PROJECT'), SOCIAL_MSG_ERROR);

                return $this->redirect($defaultRedirect);
            }

        }


        // check if the current logged in user blocked by the project creator or not.
        if ($this->my->id != $project->creator_uid) {
            if(FD::user()->isBlockedBy($project->creator_uid)) {
                return JError::raiseError(404, JText::_('COM_EASYSOCIAL_PROJECTS_PROJECT_UNAVAILABLE'));
            }
        }

        // Append additional opengraph details
        $opengraph = FD::opengraph();

        $opengraph->addUrl($project->getPermalink(true, true));
        $opengraph->addType( 'article' );
        $opengraph->addTitle($project->getName());
        $opengraph->addDescription($project->getDescription());
        $opengraph->addImage($project->getAvatar());

        // render the meta tags here.
        $opengraph->render();

        // Set the page title
        FD::page()->title($project->getName());

        // Set the breadcrumbs
        FD::page()->breadcrumb(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS'), FRoute::projects());
        FD::page()->breadcrumb($project->getName());

        $this->set('guest', $guest);
        $this->set('project', $project);

        if (!$this->my->isSiteAdmin() && !$project->isOpen() && !$guest->isGuest() && (!$project->isGroupProject() || ($project->isGroupProject() && !$project->getGroup()->isMember()))) {
            return parent::display('site/projects/restricted');
        }

        // Increment the hit counter
        $project->hit();

        // stream pagination
        $startlimit = JRequest::getInt( 'limitstart' , 0 );

        // Filter stream item by specific context type
        $context = $this->input->get('app', '', 'cmd');

        $this->set('context', $context);

        // Get a list of filters
        $filters = $project->getFilters($this->my->id);

        $this->set('filters', $filters);

        // Load up the stream model
        $streamModel= ES::model('Stream');
        $appFilters = $streamModel->getAppFilters(SOCIAL_TYPE_PROJECT);

        $this->set('appFilters', $appFilters);

        // Get all apps for the project
        $appsModel = FD::model('Apps');
        $apps = $appsModel->getProjectApps($project->id);

        // Load css files for the apps
        foreach ($apps as $app) {
            $app->loadCss();
        }

        $contents = '';
        $isAppView = false;

        // Determines if the current page is loading a specific app item
        $appId = $this->input->get('appId', 0, 'int');

        if ($appId) {
            $app = FD::table('App');
            $app->load($appId);
            $app->loadCss();

            FD::page()->title($project->getName() . ' - ' . $app->get('title'));

            $appsLib  = FD::apps();
            $contents = $appsLib->renderView(SOCIAL_APPS_VIEW_TYPE_EMBED, 'projects', $app, array('projectId' => $project->id));

            $isAppView = true;
        }

        // Determine if the current request is for "tags"
        $hashtag = $this->input->get('tag', '');

        // hashtagalias used for the header hashtag stream
        $hashtagAlias = $this->input->get('tag', '', 'default');

        // Type can be info or timeline
        // If type is info, then we load the info tab first instead of showing timleine first
        $type = $this->input->get('type', '', 'cmd');

        // @since 1.3.7
        // If type is empty, means we want to get the default view
        // Previously timeline is always the default
        // @since 1.4.6
        // If it is a hashtag view, let the timeline be the default display
        if (!$isAppView && empty($type) && empty($hashtag)) {
            $type = FD::config()->get('projects.item.display', 'timeline');
        }
        
        // Determines if the current request is to filter specific items
        $filterId = $this->input->get('filterId', 0, 'int');

        // Load Stream filter table
        $streamFilter = FD::table('StreamFilter');

        if ($filterId) {
            $streamFilter->load($filterId);
        }

        $this->set('filterId', $filterId);

        // If the current view is to display filters form
        if ($type == 'filterForm' && $guest->isGuest()) {
            $theme = FD::themes();

            $theme->set('controller', 'projects');
            $theme->set('filter', $streamFilter);
            $theme->set('uid', $project->id);

            $contents = $theme->output('site/stream/form.edit');
        }

        if ($type == 'info') {
            FD::language()->loadAdmin();
            FD::language()->loadSite(null, true);

            $currentStep = JRequest::getInt('step', 1);

            $steps = FD::model('Steps')->getSteps($project->category_id, SOCIAL_TYPE_CLUSTERS, SOCIAL_PROJECT_VIEW_DISPLAY);

            $fieldsLib = FD::fields();

            $fieldsLib->init(array('privacy' => false));

            $fieldsModel = FD::model('Fields');

            $index = 1;

            foreach ($steps as $step) {
                $step->fields = $fieldsModel->getCustomFields(array('step_id' => $step->id, 'data' => true, 'dataId' => $project->id, 'dataType' => SOCIAL_TYPE_PROJECT, 'visible' => SOCIAL_PROJECT_VIEW_DISPLAY));

                if (!empty($step->fields)) {
                    $args = array($project);

                    $fieldsLib->trigger('onDisplay', SOCIAL_FIELDS_GROUP_PROJECT, $step->fields, $args);
                }

                $step->hide = true;

                foreach ($step->fields as $field) {
                    // As long as one of the field in the step has an output, then this step shouldn't be hidden
                    // If step has been marked false, then no point marking it as false again
                    // We don't break from the loop here because there is other checking going on
                    if (!empty($field->output) && $step->hide === true ) {
                        $step->hide = false;
                    }
                }

                if ($index === 1) {
                    $step->url = FRoute::projects(array('layout' => 'item', 'id' => $project->getAlias(), 'type' => 'info'), false);
                } else {
                    $step->url = FRoute::projects(array('layout' => 'item', 'id' => $project->getAlias(), 'type' => 'info', 'infostep' => $index), false);
                }

                $step->title = $step->get('title');

                $step->active = !$step->hide && $currentStep == $index;

                if ($step->active) {
                    $theme = FD::themes();

                    $theme->set('fields', $step->fields);

                    $contents = $theme->output('site/projects/item.info');
                }

                $step->index = $index;

                $index++;
            }

            $this->set('infoSteps', $steps);
        }

        $this->set('appId', $appId);
        $this->set('apps', $apps);
        $this->set('type', $type);
        $this->set('contents', $contents);

        if (!empty($contents)) {
            return parent::display('site/projects/item');
        }

        // If no content then only we proceed to get the stream
        $stream = FD::stream();

        //lets get the sticky posts 1st
        $stickies = $stream->getStickies(array('clusterId' => $project->id, 'clusterType' => $project->cluster_type, 'limit' => 0));
        if ($stickies) {
            $stream->stickies = $stickies;
        }

        $streamOptions = array('clusterId' => $project->id, 'clusterType' => $project->cluster_type, 'nosticky' => true);

        // Load the story
        $story = FD::story($project->cluster_type);
        $story->setCluster($project->id, $project->cluster_type);
        $story->showPrivacy(false);

        if (!empty($hashtag)) {
            $tag = $stream->getHashTag($hashtag);

            if (!empty($tag->id)) {
                $this->set('hashtag', $tag->title);
                $this->set('hashtagAlias', $hashtagAlias);

                $story->setHashtags(array($tag->title));

                $streamOptions['tag'] = array($tag->title);
            }
        }

        if (!empty($streamFilter->id)) {
            $tags = $streamFilter->getHashtag();
            $tags = explode(',', $tags);

            $streamOptions['tag'] = $tags;
        }

        // Only allow users with access to post into this project
        if ($guest->isGuest() || $this->my->isSiteAdmin()) {
            $stream->story = $story;
        }

        $streamOptions['startlimit'] = $startlimit;

        if ($context) {
            $streamOptions['context'] = $context;
        }

        $stream->get($streamOptions);

        // RSS
        if ($this->config->get('stream.rss.enabled')) {
            $this->addRss(FRoute::projects(array('id' => $project->getAlias(), 'layout' => 'item'), false));
        }

        $this->set('rssLink', $this->rssLink);
        $this->set('stream', $stream);

        parent::display('site/projects/item');
    }

    /**
     * Displays the category item page.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     */
    public function category()
    {
        // Check if projects is enabled.
        $this->checkFeature();

        FD::checkCompleteProfile();

        // Get the current category
        $id = $this->input->get('id', 0, 'int');

        // Pagination for the stream
        $startlimit = $this->input->get('limitstart', 0, 'int');

        $category = FD::table('ProjectCategory');
        $state = $category->load($id);

        if (!$state) {
            FD::info()->set(false, JText::_('COM_EASYSOCIAL_PROJECTS_INVALID_CATEGORY_ID'), SOCIAL_MSG_ERROR);

            return $this->redirect(FRoute::projects());
        }

        FD::language()->loadAdmin();

        FD::page()->title($category->get('title'));

        // Add breadcrumbs
        FD::page()->breadcrumb(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS'), FRoute::projects());
        FD::page()->breadcrumb($category->get('title'));

        $model = FD::model('Projects');
        $categoryModel = FD::model('ProjectCategories');

        $projects = $model->getProjects(array('state' => SOCIAL_STATE_PUBLISHED, 'sort' => 'random', 'category' => $category->id, 'featured' => false, 'limit' => 5, 'limitstart' => 0, 'type' => array(SOCIAL_PROJECT_TYPE_PUBLIC, SOCIAL_PROJECT_TYPE_PRIVATE)));

        $featuredProjects = $model->getProjects(array('state' => SOCIAL_STATE_PUBLISHED, 'sort' => 'random', 'category' => $category->id, 'featured' => true, 'limit' => 5, 'limitstart' => 0, 'type' => array(SOCIAL_PROJECT_TYPE_PUBLIC, SOCIAL_PROJECT_TYPE_PRIVATE)));

        $randomGuests = $categoryModel->getRandomCategoryGuests($category->id);

        $randomAlbums = $categoryModel->getRandomCategoryAlbums($category->id);

        $stats = $categoryModel->getCreationStats($category->id);

        $totalProjects = $model->getTotalProjects(array('state' => SOCIAL_STATE_PUBLISHED, 'category' => $category->id, 'type' => array(SOCIAL_PROJECT_TYPE_PUBLIC, SOCIAL_PROJECT_TYPE_PRIVATE)));

        $totalAlbums = $categoryModel->getTotalAlbums($category->id);

        $stream = FD::stream();
        $stream->get(array('clusterCategory' => $category->id, 'clusterType' => SOCIAL_TYPE_PROJECT, 'startlimit' => $startlimit));

        $this->set('projects', $projects);
        $this->set('featuredProjects', $featuredProjects);
        $this->set('randomGuests', $randomGuests);
        $this->set('randomAlbums', $randomAlbums);
        $this->set('totalProjects', $totalProjects);
        $this->set('totalAlbums', $totalAlbums);
        $this->set('stats', $stats);
        $this->set('stream', $stream);

        $this->set('category', $category);

        return parent::display('site/projects/category');
    }

    /**
     * Post action after saving a filter to redirect back to project item.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     */
    public function saveFilter()
    {
        $projectId = JRequest::getInt('uid');
        $project = FD::project($projectId);

        if ($this->hasErrors()) {
            FD::info()->set($this->getMessage());
        }

        $this->redirect($project->getPermalink());
    }


    /**
     * Allows viewer to view a file
     *
     * @since   1.3
     * @access  public
     */
    public function preview()
    {
        // Check if projects is enabled.
        $this->checkFeature();

        // Currently only registered users are allowed to view a file.
        FD::requireLogin();

        // Get the file id from the request
        $id = $this->input->get('fileid', 0, 'int');

        $file = FD::table('File');
        $file->load($id);

        if(!$file->id || !$id) {
            // Throw error message here.
            $this->redirect(FRoute::dashboard(array(), false));
            $this->close();
        }

        // Load up the project
        $project = FD::project($file->uid);

        // Ensure that the user is really allowed to view this item
        if (!$project->canViewItem()) {
            // Throw error message here.
            $this->redirect(FRoute::dashboard(array(), false));
            $this->close();
        }

        $file->preview();
        exit;
    }

    /**
     * Post action after a guest response from an project to redirect back to the project.
     *
     * @since   1.3
     * @access  public
     */
    public function guestResponse()
    {
        FD::info()->set($this->getMessage());

        $id = $this->input->getInt('id', 0);

        // Load the project
        $project = FD::project($id);

        if (empty($project) || empty($project->id)) {
            return $this->redirect(FRoute::projects());
        }

        return $this->redirect($project->getPermalink());
    }

    /**
     * Post action after approving an project to redirect to the project item page.
     *
     * @since   1.3
     * @access  public
     */
    public function approveProject($project = null)
    {
        $createRecurring = !empty($project) && $project->getParams()->exists('recurringData');

        if (!$createRecurring) {
            $this->info->set($this->getMessage());

            if ($this->hasErrors()) {
                return $this->redirect(FRoute::projects());
            }

            return $this->redirect($project->getPermalink());
        }

        FD::page()->breadcrumb(JText::_('COM_EASYSOCIAL_PAGE_TITLE_PROJECTS'), FRoute::projects());
        FD::page()->breadcrumb($project->getName(), $project->getPermalink());
        FD::page()->breadcrumb(JText::_('COM_EASYSOCIAL_PAGE_TITLE_CREATE_RECURRING_PROJECT'));

        FD::page()->title(JText::_('COM_EASYSOCIAL_PAGE_TITLE_CREATE_RECURRING_PROJECT'));

        $params = $project->getParams();

        // Get the recurring schedule
        $schedule = FD::model('Projects')->getRecurringSchedule(array(
            'projectStart' => $project->getProjectStart(),
            'end' => $params->get('recurringData')->end,
            'type' => $params->get('recurringData')->type,
            'daily' => $params->get('recurringData')->daily
        ));

        $this->set('schedule', $schedule);

        $this->set('project', $project);

        echo parent::display('site/projects/createRecurring');
    }

    /**
     * Post action after rejecting an project to redirect to the project listing page.
     *
     * @since   1.3
     * @access  public
     */
    public function rejectProject($project = null)
    {
        $this->info->set($this->getMessage());

        return $this->redirect(FRoute::projects());
    }

    public function itemAction($project = null)
    {
        // Check if projects is enabled.
        $this->checkFeature();

        $this->info->set($this->getMessage());

        $action = $this->input->getString('action');
        $from = $this->input->getString('from');

        // If action is feature or unfeature, and the action is executed from the item page, then we redirect to the project item page.
        if (in_array($action, array('unfeature', 'feature')) && $from == 'item' && !empty($project)) {
            return $this->redirect($project->getPermalink());
        }

        // Else if the action is delete or unpublish, regardless of where is it executed from, we always go back to the listing page.
        return $this->redirect(FRoute::projects());
    }

    /**
     * Allows viewer to download a file from an project.
     *
     * @since   1.3
     * @access  public
     */
    public function download()
    {
        // Currently only registered users are allowed to view a file.
        FD::requireLogin();

        // Get the file id from the request
        $fileId = JRequest::getInt('fileid', null);

        $file = FD::table('File');
        $file->load($fileId);

        if (!$file->id || !$fileId) {
            // Throw error message here.
            $this->redirect(FRoute::dashboard(array(), false));
            $this->close();
        }

        // Load up the project
        $project = FD::project($file->uid);

        // Ensure that the user can really view this project
        if(!$project->canViewItem())
        {
            // Throw error message here.
            $this->redirect(FRoute::dashboard(array(), false));
            $this->close();
        }

        $file->download();
        exit;
    }

    public function updateRecurringSuccess()
    {
        FD::requireLogin();

        FD::checkToken();

        FD::info()->set(false, JText::_('COM_EASYSOCIAL_PROJECTS_UPDATED_RECURRING_SUCCESSFULLY'), SOCIAL_MSG_SUCCESS);

        // Delete session data if there is any
        $session = JFactory::getSession();
        $stepSession = FD::table('StepSession');
        $state = $stepSession->load(array('session_id' => $session->getId(), 'type' => SOCIAL_TYPE_PROJECT));
        if ($state) {
            $stepSession->delete();
        }

        $id = $this->input->getInt('id');

        $project = FD::project($id);

        // Remove the post data from params
        $clusterTable = FD::table('Cluster');
        $clusterTable->load($project->id);
        $projectParams = FD::makeObject($clusterTable->params);
        unset($projectParams->postdata);
        $clusterTable->params = FD::json()->encode($projectParams);
        $clusterTable->store();

        $this->redirect($project->getPermalink());
    }

    public function createRecurringSuccess()
    {
        FD::requireLogin();

        FD::checkToken();

        FD::info()->set(false, JText::_('COM_EASYSOCIAL_PROJECTS_CREATED_SUCCESSFULLY'), SOCIAL_MSG_SUCCESS);

        // Delete session data if there is any
        $session = JFactory::getSession();
        $stepSession = FD::table('StepSession');
        $state = $stepSession->load(array('session_id' => $session->getId(), 'type' => SOCIAL_TYPE_PROJECT));

        if ($state) {
            $stepSession->delete();
        }

        $id = $this->input->getInt('id');

        $project = FD::project($id);

        // Remove the post data from params
        $clusterTable = FD::table('Cluster');
        $clusterTable->load($project->id);
        $projectParams = FD::makeObject($clusterTable->params);
        unset($projectParams->postdata);
        $clusterTable->params = FD::json()->encode($projectParams);
        $clusterTable->store();

        $this->redirect($project->getPermalink());
    }
}
