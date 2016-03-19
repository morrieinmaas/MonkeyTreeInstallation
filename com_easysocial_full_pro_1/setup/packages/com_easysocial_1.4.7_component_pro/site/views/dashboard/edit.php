if ($filter == 'project') {
$id = $this->input->get('projectId', 0, 'int');
$project   = FD::project($id);
$projectId = $project->id;

// Check if the user is a member of the group
if (!$project->getGuest()->isGuest()) {
$this->setMessage(JText::_('COM_EASYSOCIAL_STREAM_GROUPS_NO_PERMISSIONS'), SOCIAL_MSG_ERROR);
$this->info->set($this->getMessage());
return $this->redirect(FRoute::dashboard(array(), false));
}

// When posting stories into the stream, it should be made to the group
$story = FD::get('Story', SOCIAL_TYPE_PROJECT);
$story->setCluster($project->id, SOCIAL_TYPE_PROJECT);
$story->showPrivacy(false);
$stream->story 	= $story;

//lets get the sticky posts 1st
$stickies = $stream->getStickies(array('clusterId' => $project->id, 'clusterType' 	=> SOCIAL_TYPE_PROJECT, 'limit' => 0));
if ($stickies) {
$stream->stickies = $stickies;
}

$stream->get(array('clusterId' => $project->id , 'clusterType' => SOCIAL_TYPE_PROJECT, 'nosticky' => true, 'startlimit' => $startlimit));
}