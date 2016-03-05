/**
* Prepares the upload cover stream for project.
*
* @since	1.3
* @access	public
*/
public function prepareProjectUpdateCoverStream(&$item, $privacy, $includePrivacy = true)
{
// Load the photo
$photo = $this->getPhotoFromParams($item);

// Load the project
$project = Foundry::project($item->cluster_id);

// Get the cover object for the project
$cover = $project->getCoverData();

$this->set('cover', $cover);
$this->set('photo', $photo);
$this->set('actor', $item->actor);
$this->set('project', $project);

$item->title = parent::display('streams/project/upload.cover.title');
$item->content = parent::display('streams/project/upload.cover.content');

if ($includePrivacy) {
$element = $item->context;
$uid = $item->contextId;

$item->privacy = $privacy->form($uid, $element, $item->actor->id, 'core.view', false, $item->uid);
}
}