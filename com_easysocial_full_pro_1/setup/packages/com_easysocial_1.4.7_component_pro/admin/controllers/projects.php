<?php
/**
* @package      EasySocial
* @copyright    Copyright (C) 2010 - 2014 Stack Ideas Sdn Bhd. All rights reserved.
* @license      GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined('_JEXEC') or die('Unauthorized Access');

// Include main controller
FD::import('admin:/controllers/controller');

class EasySocialControllerProjects extends EasySocialController
{
    public function __construct()
    {
        parent::__construct();

        $this->registerTask('publishCategory', 'togglePublishCategory');
        $this->registerTask('unpublishCategory', 'togglePublishCategory');

        $this->registerTask('publish', 'togglePublish');
        $this->registerTask('unpublish', 'togglePublish');

        $this->registerTask('saveCategory', 'saveCategory');
        $this->registerTask('applyCategory', 'saveCategory');
        $this->registerTask('saveCategoryNew', 'saveCategory');

        $this->registerTask('makeFeatured', 'toggleDefault');
        $this->registerTask('removeFeatured', 'toggleDefault');

        $this->registerTask('save', 'store');
        $this->registerTask('apply', 'store');
        $this->registerTask('savenew', 'store');
    }

    public function store()
    {
        FD::checkToken();

        FD::language()->loadSite();

        $my = FD::user();

        $view = $this->getCurrentView();

        $task = $this->getTask();

        $id = JRequest::getInt('id');

        $project = FD::project($id);

        $isNew = empty($project->id);

        $post = JRequest::get('POST');

        $options = array();

        if ($isNew) {
            $project->category_id = JRequest::getInt('category_id');
            $project->creator_uid = $my->id;
            $project->creator_type = SOCIAL_TYPE_USER;
            $project->state = SOCIAL_STATE_PUBLISHED;
            $project->key = md5(FD::date()->toSql() . $my->password . uniqid());
        } else {
            $options['data'] = true;
            $options['dataId'] = $project->id;
            $options['dataType'] = SOCIAL_FIELDS_GROUP_PROJECT;
        }

        $options['uid'] = $project->category_id;
        $options['group'] = SOCIAL_FIELDS_GROUP_PROJECT;

        $fields = FD::model('fields')->getCustomFields($options);

        $registry = FD::registry();

        $disallowed = array(FD::token(), 'option', 'task', 'controller');

        foreach ($post as $key => $value) {
            if (!in_array($key, $disallowed)) {
                if (is_array($value)) {
                    $value = FD::json()->encode($value);
                }

                $registry->set($key, $value);
            }
        }

        $data = $registry->toArray();

        $fieldsLib = FD::fields();

        $args = array(&$data, &$project);

        $errors = $fieldsLib->trigger('onAdminEditValidate', SOCIAL_FIELDS_GROUP_PROJECT, $fields, $args);

        if (!empty($errors)) {
            $view->setMessage(JText::_('COM_EASYSOCIAL_PROJECTS_FORM_SAVE_ERRORS'), SOCIAL_MSG_ERROR);

            JRequest::set($data, 'POST');

            return $view->call('form', $errors);
        }

        $errors = $fieldsLib->trigger('onAdminEditBeforeSave', SOCIAL_FIELDS_GROUP_PROJECT, $fields, $args);

        if (!empty($errors)) {
            $view->setMessage(JText::_('COM_EASYSOCIAL_PROJECTS_FORM_SAVE_ERRORS'), SOCIAL_MSG_ERROR);

            JRequest::set($data, 'POST');

            return $view->call('form', $errors);
        }

        $project->bind($data);

        $project->save();

        if ($isNew) {
            FD::access()->log('projects.limit', $my->id, $project->id, SOCIAL_TYPE_PROJECT);

            $project->createOwner();
        }

        $args = array(&$data, &$project);

        $fieldsLib->trigger('onAdminEditAfterSave', SOCIAL_FIELDS_GROUP_PROJECT, $fields, $args);

        $project->bindCustomFields($data);

        $args = array(&$data, &$project);

        $fieldsLib->trigger('onAdminEditAfterSaveFields', SOCIAL_FIELDS_GROUP_PROJECT, $fields, $args);

        if ($isNew) {
            $project->createStream('create', $project->creator_uid, $project->creator_type);
        }

        // Jason: We do not want to create "update" stream if the edit occurs at backend?
        /*else {

            // Only create if applyRecurring is false or project is not a child
            // applyRecurring && parent = true
            // applyRecurring && child = false
            // !applyRecurring && parent = true
            // !applyRecurring && child = true
            if (empty($data['applyRecurring']) || !$project->isRecurringProject()) {
                $project->createStream('update', $my->id, SOCIAL_TYPE_USER);
            }
        }*/

        $message = JText::_($isNew ? 'COM_EASYSOCIAL_PROJECTS_FORM_CREATE_SUCCESS' : 'COM_EASYSOCIAL_PROJECTS_FORM_UPDATE_SUCCESS');

        $view->setMessage($message, SOCIAL_MSG_SUCCESS);

        return $view->call(__FUNCTION__, $task, $project);
    }

    /**
     * Deletes the project from the site.
     *
     * @since   1.3
     * @access  public
     * @param   string
     * @return
     */
    public function delete()
    {
        // Check for request forgeries
        FD::checkToken();

        // Get the project id's.
        $ids = $this->input->get('cid', '', 'array');

        // Check for empty id's.
        if (empty($ids)) {
            $this->view->setMessage(JText::_('COM_EASYSOCIAL_PROJECTS_DELETE_FAILED'), SOCIAL_MSG_ERROR);
            return $this->view->call(__FUNCTION__);
        }

        // Go through each of the project
        foreach ($ids as $id) {
            $project = FD::project((int) $id);
            $project->delete();
        }

        $this->view->setMessage(JText::_('COM_EASYSOCIAL_PROJECTS_DELETE_SUCCESS'), SOCIAL_MSG_SUCCESS);
        return $this->view->call(__FUNCTION__);
    }

    public function saveCategory()
    {
        FD::checkToken();

        $post = JRequest::get('post');

        $view = $this->getCurrentView();

        $category = FD::table('ProjectCategory');

        $id = JRequest::getInt('id');
        $category->load($id);

        $isNew = empty($category->id);

        $category->bind($post);

        $state = $category->store();

        if (!$state) {
            $view->setMessage(JText::sprintf('COM_EASYSOCIAL_PROJECT_CATEGORY_SAVE_ERROR', $category->getError()), SOCIAL_MSG_ERROR);
            return $view->call(__FUNCTION__, $category);
        }

        $categoryAccess = JRequest::getVar('create_access');
        $category->bindCategoryAccess('create', $categoryAccess);

        $file = JRequest::getVar('avatar', '', 'FILES');

        if (!empty($file['tmp_name'])) {
            $category->uploadAvatar($file);
        }

        $postfields = JRequest::getVar('fields', $default = null, $hash = 'POST', $type = 'none', $mask = JREQUEST_ALLOWRAW);

        if (!empty($postfields)) {
            $fieldsData = FD::json()->decode($postfields);

            $fieldsLib = FD::fields();
            $fieldsLib->saveFields($category->id, SOCIAL_TYPE_CLUSTERS, $fieldsData);
        }

        if (isset($post['access'])) {
            $category->bindAccess($post['access']);
        }

        $message = JText::_($isNew ? 'COM_EASYSOCIAL_PROJECT_CATEGORY_CREATE_SUCCESS' : 'COM_EASYSOCIAL_PROJECT_CATEGORY_UPDATE_SUCCESS');

        $view->setMessage($message, SOCIAL_MSG_SUCCESS);

        return $view->call(__FUNCTION__, $category);
    }

    public function deleteCategory()
    {
        FD::checkToken();

        $ids = JRequest::getVar('cid');

        $view = $this->getCurrentView();

        if (empty($ids)) {
            $view->setMessage(JText::_('COM_EASYSOCIAL_PROJECT_CATEGORY_DELETE_FAILED'), SOCIAL_MSG_ERROR);
            return $view->call(__FUNCTION__);
        }

        foreach ($ids as $id) {
            $category = FD::table('ProjectCategory');
            $category->load($id);

            $category->delete();
        }

        $view->setMessage(JText::_('COM_EASYSOCIAL_PROJECT_CATEGORY_DELETE_SUCCESS'), SOCIAL_MSG_SUCCESS);
        return $view->call(__FUNCTION__);
    }

    public function togglePublish()
    {
        FD::checkToken();

        $action = $this->getTask();

        $ids = JRequest::getVar('cid');
        $ids = FD::makeArray($ids);

        if (empty($ids)) {
            $message = JText::_($action === 'publish' ? 'COM_EASYSOCIAL_PROJECTS_PUBLISHED_FAILED' : 'COM_EASYSOCIAL_PROJECTS_UNPUBLISHED_FAILED');

            $view->setMessage($message, SOCIAL_MSG_ERROR);
            return $view->call(__FUNCTION__);
        }

        $view = $this->getCurrentView();

        $table = FD::table('project');

        $table->$action($ids);

        $message = JText::_($action === 'publish' ? 'COM_EASYSOCIAL_PROJECTS_PUBLISHED_SUCCESS' : 'COM_EASYSOCIAL_PROJECTS_UNPUBLISHED_SUCCESS');

        $view->setMessage($message, SOCIAL_MSG_SUCCESS);
        return $view->call(__FUNCTION__);
    }

    public function togglePublishCategory()
    {
        FD::checkToken();

        $action = str_replace('Category', '', $this->getTask());

        $ids = JRequest::getVar('cid');
        $ids = FD::makeArray($ids);

        if (empty($ids)) {
            $message = JText::_($action === 'publish' ? 'COM_EASYSOCIAL_PROJECT_CATEGORY_PUBLISHED_FAILED' : 'COM_EASYSOCIAL_PROJECT_CATEGORY_UNPUBLISHED_FAILED');

            $view->setMessage($message, SOCIAL_MSG_ERROR);
            return $view->call(__FUNCTION__);
        }

        $view = $this->getCurrentView();

        $table = FD::table('ProjectCategory');

        $table->$action($ids);

        $message = JText::_($action === 'publish' ? 'COM_EASYSOCIAL_PROJECT_CATEGORY_PUBLISHED_SUCCESS' : 'COM_EASYSOCIAL_PROJECT_CATEGORY_UNPUBLISHED_SUCCESS');

        $view->setMessage($message, SOCIAL_MSG_SUCCESS);
        return $view->call(__FUNCTION__);
    }

    public function approve()
    {
        FD::checkToken();

        $ids = JRequest::getVar('cid');
        $ids = FD::makeArray($ids);

        $view = $this->getCurrentView();

        $hasRecur = array();

        foreach ($ids as $id) {
            $project = FD::project($id);
            $project->approve();
        }

        $view->setMessage(JText::_('COM_EASYSOCIAL_PROJECT_APPROVE_SUCCESS'), SOCIAL_MSG_SUCCESS);
        return $view->call(__FUNCTION__);
    }

    public function reject()
    {
        FD::checkToken();

        $ids = JRequest::getVar('cid');
        $ids = FD::makeArray($ids);

        $view = $this->getCurrentView();

        foreach ($ids as $id) {
            $project = FD::project($id);
            $project->reject();
        }

        $view->setMessage(JText::_('COM_EASYSOCIAL_PROJECT_REJECT_SUCCESS'), SOCIAL_MSG_SUCCESS);
        return $view->call(__FUNCTION__);
    }

    public function removeCategoryAvatar()
    {
        FD::checkToken();

        $view = $this->getCurrentView();

        $id = JRequest::getInt('id');

        $category = FD::table('ProjectCategory');
        $category->load($id);

        $category->removeAvatar();

        return $view->call(__FUNCTION__);
    }

    public function inviteGuests()
    {
        FD::checkToken();

        $view = $this->getCurrentView();

        $projectid = JRequest::getInt('id');

        $userids = JRequest::getString('guests');

        $userids = FD::json()->decode($userids);

        if (empty($userids) || !is_array($userids)) {
            $view->setMessage(JText::_('COM_EASYSOCIAL_PROJECTS_INVITE_GUESTS_FAILED'), SOCIAL_MSG_ERROR);
            return $view->call(__FUNCTION__);
        }

        $me = FD::user();

        $now = FD::date()->toSql();

        $count = 0;

        foreach ($userids as $id) {
            $member = FD::table('ProjectGuest');
            $project = FD::project($projectid);
            $state = $member->load(array('uid' => $id, 'type' => SOCIAL_TYPE_USER, 'cluster_id' => $projectid));

            if ($state) {
                continue;
            }

            // Inviting users cannot assume that user is definitely going. Hence state is always SOCIAL_PROJECT_GUEST_INVITED
            $member->cluster_id = $projectid;
            $member->uid = $id;
            $member->type = SOCIAL_TYPE_USER;
            $member->created = $now;
            $member->state = SOCIAL_PROJECT_GUEST_INVITED;
            $member->owner = 0;
            $member->admin = 0;
            $member->invited_by = $me->id;

            $member->store();

            $project->invite($id, $me->id);

            $count++;
        }     

        $view->setMessage(JText::sprintf('COM_EASYSOCIAL_PROJECTS_INVITE_GUESTS_SUCCESS', $count), SOCIAL_MSG_SUCCESS);

        return $view->call(__FUNCTION__);
    }

    /**
     * Allows caller to remove a guest from an project
     *
     * @since   1.3
     * @access  public
     * @param   string
     * @return
     */
    public function removeGuests()
    {
        FD::checkToken();

        $view = $this->getCurrentView();
        $cids = $this->input->get('cid', array(), 'array');

        if (empty($cids)) {
            $view->setMessage(JText::_('COM_EASYSOCIAL_PROJECTS_REMOVE_GUESTS_FAILED'), SOCIAL_MSG_ERROR);
            return $view->call(__FUNCTION__);
        }

        $count = 0;

        foreach ($cids as $cid) {
            $node = FD::table('ProjectGuest');
            $state = $node->load($cid);

            if (!$state || $node->isAdmin() || $node->isOwner()) {
                continue;
            }


            $state = $node->delete();

            if ($state) {
                $count++;
            }
        }

        $view->setMessage(JText::sprintf('COM_EASYSOCIAL_PROJECTS_REMOVE_GUESTS_SUCCESS', $count), SOCIAL_MSG_SUCCESS);

        return $view->call(__FUNCTION__);
    }

    public function approveGuests()
    {
        FD::checkToken();

        $view = $this->getCurrentView();

        $cids = JRequest::getVar('cid');

        if (empty($cids)) {
            $view->setMessage(JText::_('COM_EASYSOCIAL_PROJECTS_APPROVE_GUESTS_FAILED'), SOCIAL_MSG_ERROR);
            return $view->call(__FUNCTION__);
        }

        $count = 0;

        foreach ($cids as $cid) {
            $node = FD::table('ProjectGuest');
            $state = $node->load($cid);

            // If node is not in pending, we do not want to forcefully change the guest's state to going/maybe/notgoing/etc.
            // We only strictly approve guest that is in pending.
            if (!$state || !$node->isPending() || $node->isAdmin() || $node->isOwner()) {
                continue;
            }

            $state = $node->approve();

            if ($state) {
                $count++;
            }
        }

        $view->setMessage(JText::sprintf('COM_EASYSOCIAL_PROJECTS_APPROVE_GUESTS_SUCCESS', $count), SOCIAL_MSG_SUCCESS);

        return $view->call(__FUNCTION__);
    }

    public function switchOwner()
    {
        FD::checkToken();

        $view = $this->getCurrentView();

        $ids = JRequest::getVar('ids');
        $ids = FD::makeArray($ids);

        $userId = JRequest::getint('userId');

        if (empty($ids) || empty($userId)) {
            $view->setMessage(JText::_('COM_EASYSOCIAL_PROJECTS_SWITCH_OWNER_FAILED'), SOCIAL_MSG_ERROR);

            return $view->call(__FUNCTION__);
        }

        foreach ($ids as $id) {
            $project = FD::project($id);

            FD::access()->switchLogAuthor('projects.limit', $project->getCreator()->id, $project->id, SOCIAL_TYPE_PROJECT, $userId );

            $project->switchOwner($userId);
        }

        $view->setMessage(JText::_('COM_EASYSOCIAL_PROJECTS_SWITCH_OWNER_SUCCESS'), SOCIAL_MSG_SUCCESS);

        return $view->call(__FUNCTION__);
    }

    public function promoteGuests()
    {
        FD::checkToken();

        $view = $this->getCurrentView();

        $cids = JRequest::getVar('cid');

        if (empty($cids)) {
            $view->setMessage(JText::_('COM_EASYSOCIAL_PROJECTS_PROMOTE_GUESTS_FAILED'), SOCIAL_MSG_ERROR);
            return $view->call(__FUNCTION__);
        }

        $id = JRequest::getInt('id');

        $project = FD::project($id);

        $my = FD::user();

        $guest = $project->getGuest($my->id);

        if (!$my->isSiteAdmin() && !$guest->isAdmin() && !$guest->isOwner()) {
            $view->setMessage(JText::_('COM_EASYSOCIAL_PROJECTS_PROMOTE_GUESTS_FAILED'), SOCIAL_MSG_ERROR);
            return $view->call(__FUNCTION__);
        }

        $count = 0;

        foreach ($cids as $cid) {
            $g = FD::table('ProjectGuest');
            $g->load($cid);

            $g->makeAdmin();

            $count++;
        }

        if ($count > 0) {
            $view->setMessage(JText::sprintf('COM_EASYSOCIAL_PROJECTS_PROMOTE_GUESTS_SUCCESS', $count), SOCIAL_MSG_SUCCESS);
        }

        $view->call(__FUNCTION__);
    }

    /**
     * Allows admin to toggle featured groups
     *
     * @since   1.3
     * @access  public
     */
    public function toggleDefault()
    {
        // Check for request forgeries
        FD::checkToken();

        // Get the project object
        $ids = $this->input->get('cid', array(), 'array');

        // Get the current task
        $task = $this->getTask();

        // Default message
        $message = 'COM_EASYSOCIAL_PROJECTS_SET_FEATURED_SUCCESSFULLY';

        foreach ($ids as $id) {
            $id = (int) $id;

            $project = FD::project($id);

            if ($task == 'toggleDefault') {

                if ($project->featured) {
                    $project->removeFeatured();
                    $message = 'COM_EASYSOCIAL_PROJECTS_REMOVED_FEATURED_SUCCESSFULLY';
                } else {
                    $project->setFeatured();
                }
            }

            if ($task == 'makeFeatured') {
                $project->setFeatured();
            }

            if ($task == 'removeFeatured') {
                $project->removeFeatured();
                $message = 'COM_EASYSOCIAL_PROJECTS_REMOVED_FEATURED_SUCCESSFULLY';
            }
        }

        $this->view->setMessage($message, SOCIAL_MSG_SUCCESS);

        return $this->view->call(__FUNCTION__);
    }

    public function demoteGuests()
    {
        FD::checkToken();

        $view = $this->getCurrentView();

        $cids = JRequest::getVar('cid');

        if (empty($cids)) {
            $view->setMessage(JText::_('COM_EASYSOCIAL_PROJECTS_DEMOTE_GUESTS_FAILED'), SOCIAL_MSG_ERROR);
            return $view->call(__FUNCTION__);
        }

        $id = JRequest::getInt('id');

        $project = FD::project($id);

        $my = FD::user();

        $guest = $project->getGuest($my->id);

        if (!$my->isSiteAdmin() && !$guest->isOwner()) {
            $view->setMessage(JText::_('COM_EASYSOCIAL_PROJECTS_DEMOTE_GUESTS_FAILED'), SOCIAL_MSG_ERROR);
            return $view->call(__FUNCTION__);
        }

        $count = 0;

        foreach ($cids as $cid) {
            $g = FD::table('ProjectGuest');
            $g->load($cid);

            $g->revokeAdmin();

            $count++;
        }

        if ($count > 0) {
            $view->setMessage(JText::sprintf('COM_EASYSOCIAL_PROJECTS_DEMOTE_GUESTS_SUCCESS', $count), SOCIAL_MSG_SUCCESS);
        }

        $view->call(__FUNCTION__);
    }

    public function moveUp()
    {
        return $this->move(-1);
    }

    public function moveDown()
    {
        return $this->move(1);
    }

    private function move($index)
    {
        // Project and Project Categories both shares the same view and controller, so here we need to check for layout first to decide which ordering to move up and down

        // $layout could be categories (to add project in the future)

        $layout = $this->input->getString('layout');

        $tablename = $layout === 'categories' ? 'projectcategory' : '';

        if (empty($tablename)) {
            return $this->view->move();
        }

        $ids = $this->input->get('cid', '', 'var');

        if (!$ids) {
            $this->view->setMessage(JText::_('COM_EASYSOCIAL_PROJECTS_CATEGORIES_INVALID_IDS'), SOCIAL_MSG_ERROR);

            return $this->view->move($layout);
        }

        $db = FD::db();

        $filter = $db->nameQuote('type') . ' = ' . $db->quote(SOCIAL_TYPE_PROJECT);

        foreach ($ids as $id) {
            $table = FD::table($tablename);
            $table->load($id);

            $table->move($index, $filter);
        }

        $this->view->setMessage(JText::_('COM_EASYSOCIAL_PROJECTS_CATEGORIES_ORDERED_SUCCESSFULLY'), SOCIAL_MSG_SUCCESS);

        return $this->view->move($layout);
    }

    public function switchCategory()
    {
        FD::checkToken();

        $ids = FD::makeArray($this->input->get('cid', '', 'var'));

        $categoryId = $this->input->getInt('category');

        $categoryModel = FD::model('ProjectCategories');

        foreach ($ids as $id) {
            $categoryModel->updateProjectCategory($id, $categoryId);
        }

        $this->view->setMessage(JText::_('COM_EASYSOCIAL_PROJECTS_SWITCH_CATEGORY_SUCCESSFUL'));
        return $this->view->call(__FUNCTION__);
    }

    public function createRecurring()
    {
        FD::checkToken();

        $projectId = $this->input->getInt('projectId');

        $schedule = $this->input->getString('datetime');

        $parentProject = FD::project($projectId);

        $duration = $parentProject->hasProjectEnd() ? $parentProject->getProjectEnd()->toUnix() - $parentProject->getProjectStart()->toUnix() : false;

        $data = $this->input->getVar('postdata');

        // Because this comes form a form, the $data['id'] might be an existing id especially if the create recurring comes from "edit"
        unset($data['id']);

        // Because this comes from a form, $data['applyRecurring'] might be 1 for applying purposes, but for creation, we do not this flag
        unset($data['applyRecurring']);

        // Mark the data as createRecurring
        $data['createRecurring'] = true;

        // Manually change the start end time
        $data['startDatetime'] = FD::date($schedule)->toSql();

        if ($duration) {
            $data['endDatetime'] = FD::date($schedule + $duration)->toSql();
        } else {
            unset($data['endDatetime']);
        }

        $my = FD::user();

        $fieldsLib = FD::fields();

        $options = array();
        $options['uid'] = $parentProject->category_id;
        $options['group'] = SOCIAL_FIELDS_GROUP_PROJECT;

        $fields = FD::model('fields')->getCustomFields($options);

        $project = new SocialProject;

        $project->category_id = $parentProject->category_id;
        $project->creator_uid = $parentProject->creator_uid;
        $project->creator_type = SOCIAL_TYPE_USER;
        $project->state = SOCIAL_STATE_PUBLISHED;
        $project->key = md5(FD::date()->toSql() . $my->password . uniqid());
        $project->parent_id = $parentProject->id;
        $project->parent_type = SOCIAL_TYPE_PROJECT;

        $args = array(&$data, &$project);

        $fieldsLib->trigger('onAdminEditBeforeSave', SOCIAL_FIELDS_GROUP_PROJECT, $fields, $args);

        $project->bind($data);

        $project->save();

        // Duplicate nodes from parent
        FD::model('Projects')->duplicateGuests($parentProject->id, $project->id);

        $args = array(&$data, &$project);

        $fieldsLib->trigger('onAdminEditAfterSave', SOCIAL_FIELDS_GROUP_PROJECT, $fields, $args);

        $project->bindCustomFields($data);

        $args = array(&$data, &$project);

        $fieldsLib->trigger('onAdminEditAfterSaveFields', SOCIAL_FIELDS_GROUP_PROJECT, $fields, $args);

        return $this->view->call(__FUNCTION__);
    }
}
