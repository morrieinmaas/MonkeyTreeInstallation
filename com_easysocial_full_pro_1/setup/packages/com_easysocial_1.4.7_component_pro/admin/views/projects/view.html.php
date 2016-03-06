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

FD::import('admin:/views/views');

class EasySocialViewProjects extends EasySocialAdminView
{
    /**
     * Displays the listings of projects at the back end
     *
     * @since   1.4
     * @access  public
     * @param   string
     * @return
     */
    public function display($tpl = null)
    {
        $this->setHeading('COM_EASYSOCIAL_PROJECTS_TITLE');
        $this->setDescription('COM_EASYSOCIAL_PROJECTS_DESCRIPTION');

        JToolbarHelper::addNew('create', JText::_('COM_EASYSOCIAL_TOOLBAR_TITLE_BUTTON_NEW'), false);
        JToolbarHelper::divider();
        JToolbarHelper::custom('switchOwner', 'vcard', '', JText::_('COM_EASYSOCIAL_CHANGE_OWNER'));
        JToolbarHelper::custom('switchCategory', 'folder', '', JText::_('COM_EASYSOCIAL_TOOLBAR_TITLE_BUTTON_SWITCH_CATEGORY'));
        JToolbarHelper::divider();
        JToolbarHelper::publishList('publish');
        JToolbarHelper::unpublishList('unpublish');
        JToolbarHelper::divider();
        JToolbarHelper::custom('makeFeatured', 'featured', '', JText::_('COM_EASYSOCIAL_MAKE_FEATURED'));
        JToolbarHelper::custom('removeFeatured', 'star', '', JText::_('COM_EASYSOCIAL_REMOVE_FEATURED'));
        JToolbarHelper::divider();
        JToolbarHelper::deleteList('', 'delete', JText::_('COM_EASYSOCIAL_TOOLBAR_TITLE_BUTTON_DELETE'));

        $model = FD::model('Projects', array('initState' => true));

        $projects = $model->getItems();

        $pagination = $model->getPagination();

        $this->set('projects', $projects);
        $this->set('pagination', $pagination);

        $search = $model->getState('search');
        $ordering = $model->getState('ordering');
        $direction = $model->getState('direction');
        $state = $model->getState('state');
        $type = $model->getState('type');
        $limit = $model->getState('limit');
        $tmpl = $this->input->getVar('tmpl');

        $this->set('search', $search);
        $this->set('ordering', $ordering);
        $this->set('direction', $direction);
        $this->set('state', $state);
        $this->set('type', $type);
        $this->set('limit', $limit);
        $this->set('tmpl', $tmpl);

        echo parent::display('admin/projects/default');
    }

    /**
     * Display function for creating an project.
     *
     * @since  1.3
     * @access public
     */
    public function form($errors = array())
    {
        JToolbarHelper::apply('apply', JText::_('COM_EASYSOCIAL_TOOLBAR_TITLE_BUTTON_SAVE'), false, false);
        JToolbarHelper::save('save', JText::_('COM_EASYSOCIAL_TOOLBAR_TITLE_BUTTON_SAVE_AND_CLOSE'));
        JToolbarHelper::save2new('savenew', JText::_('COM_EASYSOCIAL_TOOLBAR_TITLE_BUTTON_SAVE_AND_NEW'));
        JToolbarHelper::divider();
        JToolbarHelper::cancel('cancel', JText::_('COM_EASYSOCIAL_TOOLBAR_TITLE_BUTTON_CANCEL'));

        FD::language()->loadSite();

        $id = $this->input->get('id', 0, 'int');

        $project = FD::project($id);

        $category = FD::table('ProjectCategory');

        $isNew = empty($project->id);

        $this->setHeading('COM_EASYSOCIAL_PROJECTS_CREATE_PROJECT_TITLE');
        $this->setDescription('COM_EASYSOCIAL_PROJECTS_CREATE_PROJECT_DESCRIPTION');

        // Set the structure heading here.
        if (!$isNew) {
            $this->setHeading($project->title);
            $this->setDescription('COM_EASYSOCIAL_PROJECTS_EDIT_PROJECT_DESCRIPTION');

            $category->load($project->category_id);
        } else {
            // By default the published state should be published.
            $project->state = SOCIAL_STATE_PUBLISHED;

            $categoryId = JRequest::getInt('category_id');
            $category->load($categoryId);
        }

        $stepsModel = FD::model('steps');
        $steps = $stepsModel->getSteps($category->id, SOCIAL_TYPE_CLUSTERS);

        $fieldsLib = FD::fields();
        $fieldsModel = FD::model('Fields');

        $post = JRequest::get('post');
        $args = array(&$post, &$project, &$errors);

        foreach ($steps as &$step) {

            $options = array('step_id' => $step->id);

            if (!$isNew) {
                $options['data'] = true;
                $options['dataId'] = $project->id;
                $options['dataType'] = SOCIAL_TYPE_PROJECT;
            }

            $step->fields = $fieldsModel->getCustomFields($options);

            if (!empty($step->fields)) {
                $fieldsLib->trigger('onAdminEdit', SOCIAL_FIELDS_GROUP_PROJECT, $step->fields, $args);
            }
        }

        $this->set('project', $project);
        $this->set('steps', $steps);
        $this->set('category', $category);

        $guestModel = FD::model('ProjectGuests', array('initState' => true));
        $guests = $guestModel->getItems(array('projectid' => $project->id));

        $this->set('guests', $guests);
        $this->set('ordering', $guestModel->getState('ordering'));
        $this->set('direction', $guestModel->getState('direction'));
        $this->set('limit', $guestModel->getState('limit'));
        $this->set('pagination', $guestModel->getPagination());


        $activeTab = JRequest::getWord('activeTab', 'project');
        $this->set('activeTab', $activeTab);

        $this->set('isNew', $isNew);

        return parent::display('admin/projects/form');
    }

    /**
     * Post action after storing an project to redirect to the appropriate page according to the task.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     * @param  string       $task   The task action.
     * @param  SocialProject  $project  The project object.
     */
    public function store($task, $project)
    {
        // Recurring support
        // If applies to all, we need to show a "progress update" page to update all childs through ajax.
        $applyAll = $project->hasRecurringProjects() && $this->input->getInt('applyRecurring');

        // Check if need to create recurring project
        $createRecurring = !empty($project->recurringData);

        if (!$applyAll && !$createRecurring) {
            FD::info()->set($this->getMessage());

            if ($task === 'apply') {
                $activeTab = JRequest::getWord('activeTab', 'project');
                return $this->redirect(FRoute::url(array('view' => 'projects', 'layout' => 'form', 'id' => $project->id, 'activeTab' => $activeTab)));
            }

            if ($task === 'savenew') {
                return $this->redirect(FRoute::url(array('view' => 'projects', 'layout' => 'form', 'category_id' => $project->category_id)));
            }

            return $this->redirect(FRoute::url(array('view' => 'projects')));
        }

        $this->setHeading('COM_EASYSOCIAL_PROJECTS_APPLYING_RECURRING_PROJECT_CHANGES');
        $this->setDescription('COM_EASYSOCIAL_PROJECTS_APPLYING_RECURRING_PROJECT_CHANGES_DESCRIPTION');

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
            // Get the recurring schedule
            $schedule = FD::model('Projects')->getRecurringSchedule(array(
                'projectStart' => $project->getProjectStart(),
                'end' => $project->recurringData->end,
                'type' => $project->recurringData->type,
                'daily' => $project->recurringData->daily
            ));
        }

        $this->set('schedule', $json->encode($schedule));

        $this->set('task', $task);

        return parent::display('admin/projects/store');
    }

    /**
     * Post action of delete to redirect to project listing.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     */
    public function delete()
    {
        FD::info()->set($this->getMessage());

        return $this->redirect(FRoute::url(array('view' => 'projects')));
    }

    /**
     * Display function for pending project listing.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     */
    public function pending($tpl = null)
    {
        // Check access
        if (!$this->authorise('easysocial.access.projects')) {
            $this->redirect('index.php', JText::_('JERROR_ALERTNOAUTHOR') , 'error');
        }

        $this->setHeading('COM_EASYSOCIAL_PENDING_PROJECTS_TITLE');
        $this->setDescription('COM_EASYSOCIAL_PENDING_PROJECTS_DESCRIPTION');

        JToolbarHelper::custom('approve', 'publish', 'social-publish-hover', JText::_('COM_EASYSOCIAL_APPROVE_BUTTON'), true);
        JToolbarHelper::custom('reject', 'unpublish', 'social-unpublish-hover', JText::_('COM_EASYSOCIAL_REJECT_BUTTON'), true);

        $model = FD::model('Projects', array('initState' => true));

        $model->setState('state', SOCIAL_CLUSTER_PENDING);

        $projects = $model->getItems();

        // Recurring support
        // Check if project is recurring project to add in the flag
        foreach ($projects as $project) {
            $project->isRecurring = $project->getParams()->exists('recurringData');
        }

        $pagination = $model->getPagination();

        $this->set('projects', $projects);
        $this->set('pagination', $pagination);

        $search = $model->getState('search');
        $ordering = $model->getState('ordering');
        $direction = $model->getState('direction');
        $state = $model->getState('state');
        $type = $model->getState('type');
        $limit = $model->getState('limit');

        $this->set('search', $search);
        $this->set('ordering', $ordering);
        $this->set('direction', $direction);
        $this->set('state', $state);
        $this->set('type', $type);
        $this->set('limit', $limit);

        echo parent::display('admin/projects/pending');
    }

    /**
     * Display function for project categories listing page.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     */
    public function categories($tpl = null)
    {
        // Check access
        if (!$this->authorise('easysocial.access.projects')) {
            $this->redirect('index.php', JText::_('JERROR_ALERTNOAUTHOR') , 'error');
        }

        $this->setHeading('COM_EASYSOCIAL_PROJECT_CATEGORIES_TITLE');
        $this->setDescription('COM_EASYSOCIAL_PROJECT_CATEGORIES_DESCRIPTION');

        JToolbarHelper::addNew('categoryForm', JText::_('COM_EASYSOCIAL_TOOLBAR_TITLE_BUTTON_NEW'), false);
        JToolbarHelper::divider();
        JToolbarHelper::publishList('publishCategory');
        JToolbarHelper::unpublishList('unpublishCategory');
        JToolbarHelper::divider();
        JToolbarHelper::deleteList('', 'deleteCategory', JText::_('COM_EASYSOCIAL_TOOLBAR_TITLE_BUTTON_DELETE'));

        $model = FD::model('ProjectCategories', array('initState' => true));

        $categories = $model->getItems();

        $pagination = $model->getPagination();

        $this->set('categories', $categories);
        $this->set('pagination', $pagination);

        $search = $model->getState('search');
        $ordering = $model->getState('ordering');
        $direction = $model->getState('direction');
        $state = $model->getState('state');
        $limit = $model->getState('limit');

        $this->set('search', $search);
        $this->set('ordering', $ordering);
        $this->set('direction', $direction);
        $this->set('state', $state);
        $this->set('limit', $limit);

        $this->set('simple', $this->input->getString('tmpl') == 'component');

        echo parent::display('admin/projects/categories');
    }

    /**
     * Display function for project category form.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     */
    public function categoryForm($tpl = null)
    {
        // Check access
        if (!$this->authorise('easysocial.access.projects')) {
            $this->redirect('index.php', JText::_('JERROR_ALERTNOAUTHOR') , 'error');
        }

        $id = JRequest::getInt('id');

        $category = FD::table('ProjectCategory');
        $category->load($id);

        // Set the structure heading here.
        if ($category->id) {
            $this->setHeading($category->get('title'));
            $this->setDescription('COM_EASYSOCIAL_PROJECT_CATEGORY_EDIT_DESCRIPTION');
        }
        else {
            $this->setHeading('COM_EASYSOCIAL_PROJECT_CATEGORY_CREATE_TITLE');
            $this->setDescription('COM_EASYSOCIAL_PROJECT_CATEGORY_CREATE_DESCRIPTION');

            // By default the published state should be published.
            $category->state = SOCIAL_STATE_PUBLISHED;
        }

        JToolbarHelper::apply('applyCategory', JText::_('COM_EASYSOCIAL_TOOLBAR_TITLE_BUTTON_SAVE'), false, false);
        JToolbarHelper::save('saveCategory', JText::_('COM_EASYSOCIAL_TOOLBAR_TITLE_BUTTON_SAVE_AND_CLOSE'));
        JToolbarHelper::save2new('saveCategoryNew', JText::_('COM_EASYSOCIAL_TOOLBAR_TITLE_BUTTON_SAVE_AND_NEW'));
        JToolbarHelper::divider();
        JToolbarHelper::cancel('cancel', JText::_('COM_EASYSOCIAL_TOOLBAR_TITLE_BUTTON_CANCEL'));

        $activeTab = JRequest::getWord('activeTab', 'settings');
        $createAccess = '';

        // Set properties for the template.
        $this->set('activeTab', $activeTab);
        $this->set('category', $category);

        if ($category->id) {
            FD::language()->loadSite();

            $options = array('type' => SOCIAL_APPS_TYPE_FIELDS, 'group' => SOCIAL_TYPE_PROJECT, 'state' => SOCIAL_STATE_PUBLISHED);

            // Get the available custom fields for groups
            $appsModel = FD::model('Apps');
            $defaultFields = $appsModel->getApps($options);

            // Get the steps for this id
            $stepsModel = FD::model('Steps');
            $steps = $stepsModel->getSteps($category->id, SOCIAL_TYPE_CLUSTERS);

            // Get the fields for this id
            $fieldsModel = FD::model('Fields');
            $fields = $fieldsModel->getCustomFields(array('uid' => $category->id, 'state' => 'all', 'group' => SOCIAL_TYPE_PROJECT));

            // Empty array to pass to the trigger.
            $data = array();

            // Get the fields sample output
            $lib = FD::fields();
            $lib->trigger('onSample', SOCIAL_TYPE_PROJECT, $fields, $data, array($lib->getHandler(), 'getOutput'));

            // Create a temporary storage
            $tmpFields = array();

            // Group the fields to each workflow properly
            if ($steps) {
                foreach ($steps as $step) {
                    $step->fields = array();

                    if (!empty($fields)) {
                        foreach ($fields as $field) {
                            if ($field->step_id == $step->id) {
                                $step->fields[] = $field;
                            }

                            $tmpFields[ $field->app_id ]    = $field;
                        }
                    }
                }
            }

            // We need to know the amount of core apps and used core apps
            // 1.3 Update, we split out unique apps as well
            $coreAppsCount = 0;
            $usedCoreAppsCount = 0;
            $uniqueAppsCount = 0;
            $usedUniqueAppsCount = 0;

            // hide the apps if it is a core app and it is used in the field
            if ($defaultFields) {
                foreach ($defaultFields as $app) {
                    $app->hidden = false;

                    // If app is core, increase the coreAppsCount counter
                    if ($app->core) {
                        $coreAppsCount++;
                    }

                    // If app is NOT core and unique, increase the coreAppsCount counter
                    // This is because core apps are definitely unique, so we do not want to include core apps here
                    if (!$app->core && $app->unique) {
                        $uniqueAppsCount++;
                    }

                    // Test if this app has already been assigned to the $tmpFields
                    if (isset($tmpFields[$app->id]) && $app->core) {
                        $usedCoreAppsCount++;

                        $app->hidden = true;
                    }

                    // Test if this app is NOT core and unique and has already been assigned
                    // This is because core apps are definitely unique, so we do not want to include core apps here
                    if (isset($tmpFields[$app->id]) && !$app->core && $app->unique) {
                        $usedUniqueAppsCount++;

                        $app->hidden = true;
                    }
                }
            }

            unset($tmpFields);

            // Get the creation access
            $createAccess = $category->getAccess('create');

            // We need to know if there are any core apps remain
            $coreAppsRemain = $usedCoreAppsCount < $coreAppsCount;

            // We need to know if there are any unique apps remain
            $uniqueAppsRemain = $usedUniqueAppsCount < $uniqueAppsCount;

            // Set the profiles allowed to create groups
            $this->set('createAccess', $createAccess);

            // Set the flag of coreAppsRemain
            $this->set('coreAppsRemain', $coreAppsRemain);

            // Set the flag of uniqueAppsRemain
            $this->set('uniqueAppsRemain', $uniqueAppsRemain);

            // Set the default apps to the template.
            $this->set('defaultFields', $defaultFields);

            // Set the steps for the template.
            $this->set('steps', $steps);

            // Set the fields to the template
            $this->set('fields', $fields);

            // Set the field group type to the template
            $this->set('fieldGroup', SOCIAL_FIELDS_GROUP_PROJECT);

            // Render the access form.
            $accessModel = FD::model('Access');
            $accessForm = $accessModel->getForm($category->id, SOCIAL_TYPE_PROJECT, 'access');
            $this->set('accessForm' , $accessForm);
        }

        // Set the profiles allowed to create groups
        $this->set('createAccess', $createAccess);

        echo parent::display('admin/projects/form.category');
    }

    /**
     * Post process for the task applyCategory, saveCategoryNew and saveCategory to redirect to the corresponding page.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @param   SocialTableProjectCategory    $category The project category table object.
     */
    public function saveCategory($category)
    {
        FD::info()->set($this->getMessage());

        $activeTab = $this->input->getString('activeTab', 'settings');

        if ($this->hasErrors()) {
            return $this->redirect(FRoute::url(array('view' => 'projects', 'layout' => 'categoryForm', 'activeTab' => $activeTab)));
        }

        $task = JRequest::getVar('task');

        if ($task === 'applyCategory') {
            return $this->redirect(FRoute::url(array('view' => 'projects', 'layout' => 'categoryForm', 'id' => $category->id, 'activeTab' => $activeTab)));
        }

        if ($task === 'saveCategoryNew') {
            return $this->redirect(FRoute::url(array('view' => 'projects', 'layout' => 'categoryForm', 'activeTab' => $activeTab)));
        }

        return $this->redirect(FRoute::url(array('view' => 'projects', 'layout' => 'categories', 'activeTab' => $activeTab)));
    }

    /**
     * Post action for deleteCategory to redirect to project category listing.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     */
    public function deleteCategory()
    {
        FD::info()->set($this->getMessage());

        return $this->redirect(FRoute::url(array('view' => 'projects', 'layout' => 'categories')));
    }

    /**
     * Post action after publishing or unpublishing projects to redirect to project listing.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     */
    public function togglePublish()
    {
        FD::info()->set($this->getMessage());

        return $this->redirect(FRoute::url(array('view' => 'projects')));
    }

    /**
     * Post action after publishing or unpublishing project category to redirect to project listing.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     */
    public function togglePublishCategory()
    {
        FD::info()->set($this->getMessage());

        return $this->redirect(FRoute::url(array('view' => 'projects', 'layout' => 'categories')));
    }

    /**
     * Post action after approving an project to redirect back to the pending listing.
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     */
    public function approve()
    {
        $ids = $this->input->getVar('cid');
        $ids = FD::makeArray($ids);

        $schedules = array();
        $postdatas = array();
        $projectids = array();

        foreach ($ids as $id) {
            $project = FD::project($id);

            $params = $project->getParams();

            if ($params->exists('recurringData')) {

                $schedule = FD::model('Projects')->getRecurringSchedule(array(
                    'projectStart' => $project->getProjectStart(),
                    'end' => $params->get('recurringData')->end,
                    'type' => $params->get('recurringData')->type,
                    'daily' => $params->get('recurringData')->daily
                ));

                if (!empty($schedule)) {
                    $projectids[] = $project->id;
                    $schedules[$project->id] = $schedule;
                    $postdatas[$project->id] = FD::makeObject($params->get('postdata'));
                }
            }
        }

        if (empty($schedules)) {
            FD::info()->set($this->getMessage());

            return $this->redirect(FRoute::url(array('view' => 'projects', 'layout' => 'pending')));
        }

        $this->setHeading(JText::_('COM_EASYSOCIAL_PROJECTS_APPLYING_RECURRING_PROJECT_CHANGES'));
        $this->setDescription(JText::_('COM_EASYSOCIAL_PROJECTS_APPLYING_RECURRING_PROJECT_CHANGES_DESCRIPTION'));

        $json = FD::json();

        $this->set('schedules', $json->encode($schedules));
        $this->set('postdatas', $json->encode($postdatas));
        $this->set('projectids', $json->encode($projectids));

        echo parent::display('admin/projects/approve.recurring');
    }

    public function approveRecurringSuccess()
    {
        $projectids = $this->input->getString('ids');
        $projectids = FD::makeArray($projectids);

        foreach ($projectids as $id) {
            $clusterTable = FD::table('Cluster');
            $clusterTable->load($id);
            $projectParams = FD::makeObject($clusterTable->params);
            unset($projectParams->postdata);
            $clusterTable->params = FD::json()->encode($projectParams);
            $clusterTable->store();
        }

        FD::info()->set(false, JText::_('COM_EASYSOCIAL_PROJECT_APPROVE_SUCCESS'), SOCIAL_MSG_SUCCESS);

        return $this->redirect(FRoute::url(array('view' => 'projects', 'layout' => 'pending')));
    }

    /**
     * Post action after rejecting an project to redirect back to the pending listing.
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     */
    public function reject()
    {
        FD::info()->set($this->getMessage());

        return $this->redirect(FRoute::url(array('view' => 'projects', 'layout' => 'pending')));
    }

    /**
     * Post action after inviting guests to an project to redirect back to the project form.
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     */
    public function inviteGuests()
    {
        FD::info()->set($this->getMessage());

        $id = JRequest::getInt('id');

        return $this->redirect(FRoute::url(array('view' => 'projects', 'layout' => 'form', 'id' => $id, 'activeTab' => 'guests')));
    }

    /**
     * Post action after approving guests to redirect back to the project form.
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     */
    public function approveGuests()
    {
        FD::info()->set($this->getMessage());

        $id = JRequest::getInt('id');

        return $this->redirect(FRoute::url(array('view' => 'projects', 'layout' => 'form', 'id' => $id, 'activeTab' => 'guests')));
    }

    /**
     * Post action after rejecting guests to redirect back to the project form.
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     */
    public function removeGuests()
    {
        FD::info()->set($this->getMessage());

        $id = JRequest::getInt('id');

        return $this->redirect(FRoute::url(array('view' => 'projects', 'layout' => 'form', 'id' => $id, 'activeTab' => 'guests')));
    }

    /**
     * Post action after switching an project's owner.
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     */
    public function switchOwner()
    {
        FD::info()->set($this->getMessage());

        return $this->redirect(FRoute::url(array('view' => 'projects')));
    }

    /**
     * Post action after promoting guests to redirect back to the project form.
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     */
    public function promoteGuests()
    {
        FD::info()->set($this->getMessage());

        $id = JRequest::getInt('id');

        return $this->redirect(FRoute::url(array('view' => 'projects', 'layout' => 'form', 'id' => $id, 'activeTab' => 'guests')));
    }

    /**
     * Post action after removing guests admin role to redirect back to the project form.
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     */
    public function demoteGuests()
    {
        FD::info()->set($this->getMessage());

        $id = JRequest::getInt('id');

        return $this->redirect(FRoute::url(array('view' => 'projects', 'layout' => 'form', 'id' => $id, 'activeTab' => 'guests')));
    }

    /**
     * Post process after a group is marked as featured
     *
     * @since   1.3
     * @access  public
     * @param   string
     * @return
     */
    public function toggleDefault()
    {
        FD::info()->set($this->getMessage());

        $this->redirect('index.php?option=com_easysocial&view=projects');
    }

    public function move($layout = null)
    {
        FD::info()->set($this->getMessage());

        $this->redirect('index.php?option=com_easysocial&view=projects&layout=' . $layout);
    }

    public function switchCategory()
    {
        FD::info()->set($this->getMessage());

        $this->redirect('index.php?option=com_easysocial&view=projects');
    }

    public function updateRecurringSuccess()
    {
        FD::info()->set(false, JText::_('COM_EASYSOCIAL_PROJECTS_FORM_UPDATE_SUCCESS'), SOCIAL_MSG_SUCCESS);

        $task = $this->input->getString('task');

        $projectId = $this->input->getInt('id');

        $project = FD::project($projectId);

        // Remove the post data from params
        $clusterTable = FD::table('Cluster');
        $clusterTable->load($project->id);
        $projectParams = FD::makeObject($clusterTable->params);
        unset($projectParams->postdata);
        $clusterTable->params = FD::json()->encode($projectParams);
        $clusterTable->store();

        if ($task === 'apply') {
            return $this->redirect(FRoute::url(array('view' => 'projects', 'layout' => 'form', 'id' => $project->id, 'activeTab' => 'project')));
        }

        if ($task === 'savenew') {
            return $this->redirect(FRoute::url(array('view' => 'projects', 'layout' => 'form', 'category_id' => $project->category_id)));
        }

        return $this->redirect(FRoute::url(array('view' => 'projects')));
    }
}
