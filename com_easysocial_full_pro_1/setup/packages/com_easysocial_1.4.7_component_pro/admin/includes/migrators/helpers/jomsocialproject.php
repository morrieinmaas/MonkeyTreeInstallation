<?php
/**
* @package		EasySocial
* @copyright	Copyright (C) 2010 - 2014 Stack Ideas Sdn Bhd. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined( '_JEXEC' ) or die( 'Unauthorized Access' );


jimport( 'joomla.filesystem.file' );

// FD::import( 'admin:/inclues/migrators/helpers/info' );
require_once( SOCIAL_LIB . '/migrators/helpers/info.php' );

/**
 * DB layer for EasySocial.
 *
 * @since	1.0
 * @author	Sam <sam@stackideas.com>
 */
class SocialMigratorHelperJomsocialProject
{
	// component name, e.g. com_community
	var $name  			= null;

	// migtration steps
	var $steps 			= null;

	var $info  			= null;

	var $limit 		 	= null;

	//member state mapping
	var $stateMapping   = null;

	public function __construct()
	{
		$this->info     = new SocialMigratorHelperInfo();
		$this->name  	= 'com_community';

		$this->stateMapping = array();
		$this->stateMapping['0'] = SOCIAL_PROJECT_GUEST_INVITED;
		$this->stateMapping['1'] = SOCIAL_PROJECT_GUEST_GOING;
		$this->stateMapping['2'] = SOCIAL_PROJECT_GUEST_NOT_GOING;
		$this->stateMapping['3'] = SOCIAL_PROJECT_GUEST_MAYBE;
		$this->stateMapping['5'] = SOCIAL_PROJECT_GUEST_NOT_GOING;
		$this->stateMapping['6'] = SOCIAL_PROJECT_GUEST_PENDING;

		$this->limit 	= 10; //10 items per cycle

		// do not change the steps sequence !
		$this->steps[] 	= 'projectcategory';
		$this->steps[] 	= 'projects';
		$this->steps[] 	= 'projectmembers';
		$this->steps[] 	= 'projectavatar';
		$this->steps[] 	= 'projectcover';
		$this->steps[] 	= 'projectwalls';

	}

	public function getVersion()
	{
		$exists 	= $this->isComponentExist();

		if( !$exists->isvalid )
		{
			return false;
		}

		// check JomSocial version.
		$xml		= JPATH_ROOT . '/administrator/components/com_community/community.xml';

		$parser = FD::get( 'Parser' );
		$parser->load( $xml );

		$version	= $parser->xpath( 'version' );
		$version 	= (float) $version[0];

		return $version;
	}

	public function isInstalled()
	{
		$file	= JPATH_ROOT . '/components/com_community/libraries/core.php';

		if(! JFile::exists( $file ) )
		{
			return false;
		}

		return true;
	}

	public function setUserMapping( $maps )
	{
		// do nothing.
	}

	/*
	 * return object with :
	 *     isvalid  : true or false
	 *     messsage : string.
	 *     count    : integer. item count to be processed.
	 */
	public function isComponentExist()
	{
		$obj = new stdClass();
		$obj->isvalid = false;
		$obj->count   = 0;
		$obj->message = '';

		$jsCoreFile	= JPATH_ROOT . '/components/com_community/libraries/core.php';

		if(! JFile::exists( $jsCoreFile ) )
		{
			$obj->message = 'JomSocial not found in your site. Process aborted.';
			return $obj;
		}

		// @todo check if the db tables exists or not.


		// all pass. return object

		$obj->isvalid = true;
		$obj->count   = $this->getItemCount();

		return $obj;
	}

	public function getItemCount()
	{
		$db = FD::db();
		$sql = $db->sql();

		$total = count( $this->steps );

		// project category
		$query = 'select count(1) as `total`';
		$query .= ' from `#__community_projects_category` as a';
		$query .= ' where not exists ( ';
		$query .= '		select b.`id` from `#__social_migrators` as b';
		$query .= ' 			where a.`id` = b.`oid` and b.`element` = ' . $db->Quote( 'projectcategory' ) . ' and b.`component` = ' . $db->Quote( $this->name );
		$query .= ' )';

		$sql->clear();
		$sql->raw( $query );
		$db->setQuery( $sql );
		$numTotal = $db->loadResult();
		$numTotal = ( $numTotal > 0 ) ? ceil( $numTotal / $this->limit ) : 0;
		$total = $total + $numTotal;

		// projects
		$query = 'select count(1) as `total`';
		$query .= ' from `#__community_projects` as a';
		$query .= ' where not exists ( ';
		$query .= '		select b.`id` from `#__social_migrators` as b';
		$query .= ' 			where a.`id` = b.`oid` and b.`element` = ' . $db->Quote( 'projects' ) . ' and b.`component` = ' . $db->Quote( $this->name );
		$query .= ' )';
		$query .= ' and a.`type` = ' . $db->Quote('profile');

		$sql->clear();
		$sql->raw( $query );
		$db->setQuery( $sql );
		$numTotal = $db->loadResult();
		$numTotal = ( $numTotal > 0 ) ? ceil( $numTotal / $this->limit ) : 0;
		$total = $total + $numTotal;

		// ------------  groups members
		$query = 'select count(1) as `total`';
		$query .= ' from `#__community_projects_members` as a';
		$query .= ' inner join `#__social_migrators` as c on a.`projectid` = c.`oid` and c.`element` = ' . $db->Quote( 'projects' ) . ' and c.`component` = ' . $db->Quote( 'com_community' );
		$query .= ' inner join `#__community_projects` as d on a.`projectid` = d.`id`';
		$query .= ' where not exists ( ';
		$query .= '		select b.`id` from `#__social_migrators` as b';
		$query .= ' 			where a.`memberid` = b.`oid` and b.`element` = concat_ws(' . $db->Quote('.') . ',' . $db->Quote( 'projectmembers' ) . ', a.`projectid` ) and b.`component` = ' . $db->Quote( $this->name );
		$query .= ' )';

		$sql->clear();
		$sql->raw( $query );
		$db->setQuery( $sql );
		$numTotal = $db->loadResult();

		$numTotal = ( $numTotal > 0 ) ? ceil( $numTotal / $this->limit ) : 0;
		$total = $total + $numTotal;


		// ------------  project avatar
		$query = 'select count(1) as `total`';
		$query .= ' from `#__community_projects` as a';
		$query .= ' where not exists ( ';
		$query .= '		select b.`id` from `#__social_migrators` as b';
		$query .= ' 			where a.`id` = b.`oid` and b.`element` = ' . $db->Quote( 'projectavatar' ) . ' and b.`component` = ' . $db->Quote( $this->name );
		$query .= ' )';

		$sql->clear();
		$sql->raw( $query );
		$db->setQuery( $sql );
		$numTotal = $db->loadResult();
		$numTotal = ( $numTotal > 0 ) ? ceil( $numTotal / $this->limit ) : 0;
		$total = $total + $numTotal;


		// ------------  project cover
		$query = 'select count(1) as `total`';
		$query .= ' from `#__community_projects` as a';
		$query .= ' where not exists ( ';
		$query .= '		select b.`id` from `#__social_migrators` as b';
		$query .= ' 			where a.`id` = b.`oid` and b.`element` = ' . $db->Quote( 'projectcover' ) . ' and b.`component` = ' . $db->Quote( $this->name );
		$query .= ' )';

		$sql->clear();
		$sql->raw( $query );
		$db->setQuery( $sql );
		$numTotal = $db->loadResult();
		$numTotal = ( $numTotal > 0 ) ? ceil( $numTotal / $this->limit ) : 0;
		$total = $total + $numTotal;


		// ------------  project wall post
		$query = 'select count(1) as `total`';
		$query .= ' from `#__community_activities` as a';
		$query .= ' where not exists ( ';
		$query .= '		select b.`id` from `#__social_migrators` as b';
		$query .= ' 			where a.`id` = b.`oid` and b.`element` = ' . $db->Quote( 'projectwalls' ) . ' and b.`component` = ' . $db->Quote( $this->name );
		$query .= ' )';
		$query .= ' and `app` = ' . $db->Quote( 'projects.wall' );

		$sql->clear();
		$sql->raw( $query );
		$db->setQuery( $sql );
		$numTotal = $db->loadResult();
		$numTotal = ( $numTotal > 0 ) ? ceil( $numTotal / $this->limit ) : 0;
		$total = $total + $numTotal;

		return $total;
	}

	public function process( $item )
	{
		// @debug
		$obj = new stdClass();

		if( empty( $item ) )
		{
			$item = $this->steps[0];
		}

		$result = '';

		switch( $item )
		{
			case 'projectcategory':
				$result = $this->processProjectCategory();
				break;

			case 'projects':
				$result = $this->processProjects();
				break;

			case 'projectmembers':
				$result = $this->processMembers();
				break;

			case 'projectavatar':
				$result = $this->processAvatar();
				break;

			case 'projectcover':
				$result = $this->processCover();
				break;

			case 'projectwalls':
				$result = $this->processWall();
				break;

			default:
				break;
		}

		// this is the ending part to determine if the process is already ended or not.
		if( is_null( $result ) )
		{
			$keys 		= array_keys( $this->steps, $item);
			$curSteps 	= $keys[0];

			if( isset( $this->steps[ $curSteps + 1] ) )
			{
				$item = $this->steps[ $curSteps + 1];
			}
			else
			{
				$item = null;
			}

			$obj->continue = ( is_null( $item ) ) ? false : true ;
			$obj->item 	   = $item;
			$obj->message  = ( $obj->continue ) ? 'Checking for next item to migrate....' : 'No more item found.';

			return $obj;
		}


		$obj->continue = true;
		$obj->item 	   = $item;
		$obj->message  = implode( '<br />', $result->message );

		return $obj;
	}

	private function processWall()
	{
		$db 	= FD::db();
		$sql 	= $db->sql();

		$query = 'select a.*, c.`uid` as `esprojectid`';
		$query .= ' from `#__community_activities` as a';
		$query .= ' 	inner join `#__social_migrators` as c on a.`projectid` = c.`oid` and c.`element` = ' . $db->Quote( 'projects' ) . ' and c.`component` = ' . $db->Quote( 'com_community' );
		$query .= ' where not exists ( ';
		$query .= '		select b.`id` from `#__social_migrators` as b';
		$query .= ' 			where a.`id` = b.`oid` and b.`element` = ' . $db->Quote( 'projectwalls' ) . ' and b.`component` = ' . $db->Quote( $this->name );
		$query .= ' )';
		$query .= ' and `app` = ' . $db->Quote( 'projects.wall' );
		$query .= ' ORDER BY a.`id` ASC';
		$query .= ' LIMIT ' . $this->limit;

		$sql->raw( $query );
		$db->setQuery( $sql );

		$jsWalls = $db->loadObjectList();

		if( count( $jsWalls ) <= 0 )
		{
			return null;
		}

		foreach( $jsWalls as $jsWall )
		{
			// create story stream for this group.

			$stream 		= FD::stream();

			// Get the stream template
			$template 		= $stream->getTemplate();

			$template->setActor( $jsWall->actor , SOCIAL_TYPE_USER );
			$template->setContext( '0' , SOCIAL_TYPE_STORY );


			$content = ( $jsWall->title ) ? $jsWall->title : $jsWall->content;
			$template->setContent( $content );

			$template->setVerb( 'create' );

			// Set the params to cache the group data

			$project = FD::project( $jsWall->esprojectid );
			$registry	= FD::registry();
			$registry->set( 'project' , $project );

			// Set the params to cache the group data
			$template->setParams( $registry );

			$template->setCluster( $jsWall->esprojectid, SOCIAL_TYPE_PROJECT, $project->type );

			// Set this stream to be public
			$template->setAccess( 'story.view' );

			$template->setDate( $jsWall->created );

			$streamItem 	= $stream->add( $template );

			$this->log( 'projectwalls', $jsWall->id, $streamItem->uid );

			$this->info->setInfo( 'Project wall \'' . $jsWall->id . '\' is now migrated into EasySocial as project\'s story update.' );


		}

		return $this->info;

	}

	private function processCover()
	{
		$config = FD::config();
		$db 	= FD::db();
		$sql 	= $db->sql();

		$query = 'select a.*, c.uid as `esprojectid`';
		$query .= ' from `#__community_projects` as a';
		$query .= ' inner join `#__social_migrators` as c on a.`id` = c.`oid` and c.`element` = ' . $db->Quote( 'projects' ) . ' and c.`component` = ' . $db->Quote( 'com_community' );
		$query .= ' where not exists ( ';
		$query .= '		select b.`id` from `#__social_migrators` as b';
		$query .= ' 			where a.`id` = b.`oid` and b.`element` = ' . $db->Quote( 'projectcover' ) . ' and b.`component` = ' . $db->Quote( $this->name );
		$query .= ' )';
		$query .= ' ORDER BY a.`id` ASC';
		$query .= ' LIMIT ' . $this->limit;

		$sql->raw( $query );
		$db->setQuery( $sql );

		$jsProjects = $db->loadObjectList();

		if( count( $jsProjects ) <= 0 )
		{
			return null;
		}

		foreach( $jsProjects as $jsProject )
		{
			if( !$jsProject->cover )
			{
				// no need to process further.
				$this->log( 'projectcover', $jsProject->id , $jsProject->id );

				$this->info->setInfo( 'Project ' . $jsProject->id . ' is using default cover. no migration is needed.' );
				continue;
			}

			$imagePath = JPATH_ROOT . '/' . $jsProject->cover;

			$tmp 		= explode( '/', $imagePath );
			$filename 	= $tmp[ count( $tmp ) - 1 ];

			if( !JFile::exists( $imagePath ) )
			{
				$this->log( 'projectcover', $jsProject->id , $jsProject->id );

				$this->info->setInfo( 'Project ' . $jsProject->id . ' the cover image file is not found from the server. Process aborted.');
				continue;
			}

			// lets copy this file to tmp folder 1st.
			$tmp 			= JFactory::getConfig()->get( 'tmp_path' );
			$tmpImageFile 	= $tmp . '/' . md5( JFactory::getDate()->toSql() );
			JFile::copy( $imagePath , $tmpImageFile );

			$image = FD::image();
			$image->load( $tmpImageFile );

			// $avatar	= FD::avatar( $image, $jsProject->esprojectid, SOCIAL_TYPE_PROJECT );

			// Check if there's a profile photos album that already exists.
			$albumModel	= FD::model( 'Albums' );

			// Retrieve the group's default album
			$album 	= $albumModel->getDefaultAlbum( $jsProject->esprojectid , SOCIAL_TYPE_PROJECT , SOCIAL_ALBUM_PROFILE_COVERS );
			$album->user_id = $jsProject->creator;
			$album->store();

			$photo 				= FD::table( 'Photo' );
			$photo->uid 		= $jsProject->esprojectid ;
			$photo->user_id 	= $jsProject->creator ;
			$photo->type 		= SOCIAL_TYPE_PROJECT;
			$photo->album_id 	= $album->id;
			$photo->title 		= $filename;
			$photo->caption 	= '';
			$photo->ordering	= 0;

			// We need to set the photo state to "SOCIAL_PHOTOS_STATE_TMP"
			$photo->state 		= SOCIAL_PHOTOS_STATE_TMP;

			// Try to store the photo first
			$state 		= $photo->store();

			// Push all the ordering of the photo down
			$photosModel = FD::model( 'photos' );
			$photosModel->pushPhotosOrdering( $album->id , $photo->id );

			// Render photos library
			$photoLib 	= FD::get( 'Photos' , $image );
			$storage    = $photoLib->getStoragePath($album->id, $photo->id);
			$paths 		= $photoLib->create( $storage );

			// Create metadata about the photos
			foreach( $paths as $type => $fileName )
			{
				$meta 				= FD::table( 'PhotoMeta' );
				$meta->photo_id		= $photo->id;
				$meta->group 		= SOCIAL_PHOTOS_META_PATH;
				$meta->property 	= $type;
				$meta->value		= $storage . '/' . $fileName;

				$meta->store();
			}


			// Load the cover
			$cover 	= FD::table( 'Cover' );
			$cover->uid 	= $jsProject->esprojectid;
			$cover->type 	= SOCIAL_TYPE_PROJECT;

			$cover->setPhotoAsCover( $photo->id );

			// Save the cover.
			$cover->store();

			// now we need to update back the photo item to have the cover_id and the state to published
			// We need to set the photo state to "SOCIAL_STATE_PUBLISHED"
			$photo->state 		= SOCIAL_STATE_PUBLISHED;
			$photo->store();

			if (! $album->cover_id) {
				$album->cover_id = $photo->id;
				$album->store();
			}

			// @Add stream item when a new project cover is uploaded
			// get the cover update date.
			$uploadDate = $this->getMediaUploadDate('cover.upload', $jsProject->id);

			if (!$uploadDate) {
				// if empty, then lets just use project creation date.
				$uploadDate = $jsProject->created;
			}

			$photo->addPhotosStream( 'updateCover', $uploadDate );


			// log into mgirator
			$this->log( 'projectcover', $jsProject->id , $jsProject->id );

			$this->info->setInfo( 'Project cover ' . $jsProject->id . ' is now migrated into EasySocial.' );

		}

		return $this->info;

	}

	private function getMediaUploadDate($context, $jsProjectId)
	{
		$db = FD::db();
		$sql = $db->sql();

		$query = "select `created` from `#__community_activities` where `projectid` = '$jsProjectId' and `app` = '$context' order by `id` desc limit 1";
		$sql->raw($query);

		$db->setQuery($sql);
		$result = $db->loadResult();

		return $result;
	}

	private function processAvatar()
	{
		$config = FD::config();
		$db 	= FD::db();
		$sql 	= $db->sql();

		$query = 'select a.*, c.uid as `esprojectid`';
		$query .= ' from `#__community_projects` as a';
		$query .= ' inner join `#__social_migrators` as c on a.`id` = c.`oid` and c.`element` = ' . $db->Quote( 'projects' ) . ' and c.`component` = ' . $db->Quote( 'com_community' );
		$query .= ' where not exists ( ';
		$query .= '		select b.`id` from `#__social_migrators` as b';
		$query .= ' 			where a.`id` = b.`oid` and b.`element` = ' . $db->Quote( 'projectavatar' ) . ' and b.`component` = ' . $db->Quote( $this->name );
		$query .= ' )';
		$query .= ' ORDER BY a.`id` ASC';
		$query .= ' LIMIT ' . $this->limit;

		$sql->raw( $query );
		$db->setQuery( $sql );

		$jsProjects = $db->loadObjectList();

		if( count( $jsProjects ) <= 0 )
		{
			return null;
		}

		foreach( $jsProjects as $jsProject )
		{
			if( !$jsProject->avatar )
			{
				// no need to process further.
				$this->log( 'projectavatar', $jsProject->id , $jsProject->id );

				$this->info->setInfo( 'Project ' . $jsProject->id . ' is using default avatar. no migration is needed.' );
				continue;
			}

			$imagePath = JPATH_ROOT . '/' . $jsProject->avatar;

			$tmp 		= explode( '/', $imagePath );
			$filename 	= $tmp[ count( $tmp ) - 1 ];

			if( !JFile::exists( $imagePath ) )
			{
				$this->log( 'projectavatar', $jsProject->id , $jsProject->id );

				$this->info->setInfo( 'Project ' . $jsProject->id . ' the avatar image file is not found from the server. Process aborted.');
				continue;
			}

			// lets copy this file to tmp folder 1st.
			$tmp 			= JFactory::getConfig()->get( 'tmp_path' );
			$tmpImageFile 	= $tmp . '/' . md5( JFactory::getDate()->toSql() );
			JFile::copy( $imagePath , $tmpImageFile );

			$image = FD::image();
			$image->load( $tmpImageFile );

			$avatar	= FD::avatar( $image, $jsProject->esprojectid, SOCIAL_TYPE_PROJECT );

			// Check if there's a profile photos album that already exists.
			$albumModel	= FD::model( 'Albums' );

			// Retrieve the group's default album
			$album 	= $albumModel->getDefaultAlbum( $jsProject->esprojectid , SOCIAL_TYPE_PROJECT , SOCIAL_ALBUM_PROFILE_PHOTOS );
			$album->user_id = $jsProject->creator;
			$album->store();

			$photo 				= FD::table( 'Photo' );
			$photo->uid 		= $jsProject->esprojectid ;
			$photo->user_id 	= $jsProject->creator ;
			$photo->type 		= SOCIAL_TYPE_PROJECT;
			$photo->album_id 	= $album->id;
			$photo->title 		= $filename;
			$photo->caption 	= '';
			$photo->ordering	= 0;

			// We need to set the photo state to "SOCIAL_PHOTOS_STATE_TMP"
			$photo->state 		= SOCIAL_PHOTOS_STATE_TMP;

			// Try to store the photo first
			$state 		= $photo->store();

			// Push all the ordering of the photo down
			$photosModel = FD::model( 'photos' );
			$photosModel->pushPhotosOrdering( $album->id , $photo->id );

			// Render photos library
			$photoLib 	= FD::get( 'Photos' , $image );
			$storage    = $photoLib->getStoragePath($album->id, $photo->id);
			$paths 		= $photoLib->create( $storage );

			// Create metadata about the photos
			foreach( $paths as $type => $fileName )
			{
				$meta 				= FD::table( 'PhotoMeta' );
				$meta->photo_id		= $photo->id;
				$meta->group 		= SOCIAL_PHOTOS_META_PATH;
				$meta->property 	= $type;
				$meta->value		= $storage . '/' . $fileName;

				$meta->store();
			}

			// Create the avatars now, but we do not want the store function to create stream.
			// so we pass in the option. we will create the stream ourown.
			$options = array( 'addstream' => false );
			$avatar->store( $photo, $options );


			// @Add stream item when a new project avatar is uploaded
			// get the cover update date.
			$uploadDate = $this->getMediaUploadDate('projects.avatar.upload', $jsProject->id);

			if (!$uploadDate) {
				// if empty, then lets just use project creation date.
				$uploadDate = $jsProject->created;
			}

			$photo->addPhotosStream( 'uploadAvatar', $uploadDate );


			// log into mgirator
			$this->log( 'projectavatar', $jsProject->id , $photo->id );

			$this->info->setInfo( 'Project avatar ' . $jsProject->id . ' is now migrated into EasySocial.' );

		}

		return $this->info;

	}

	private function processProjects()
	{
		$config = FD::config();
		$db 	= FD::db();
		$sql 	= $db->sql();

		$query = 'select a.*, c.uid as `escatid`';
		$query .= ' from `#__community_projects` as a';
		$query .= ' inner join `#__social_migrators` as c on a.`catid` = c.`oid` and c.`element` = ' . $db->Quote( 'projectcategory' ) . ' and c.`component` = ' . $db->Quote( 'com_community' );
		$query .= ' where not exists ( ';
		$query .= '		select b.`id` from `#__social_migrators` as b';
		$query .= ' 			where a.`id` = b.`oid` and b.`element` = ' . $db->Quote( 'projects' ) . ' and b.`component` = ' . $db->Quote( $this->name );
		$query .= ' )';
		$query .= ' and a.type = ' . $db->Quote('profile');
		$query .= ' ORDER BY a.`id` ASC';
		$query .= ' LIMIT ' . $this->limit;

		$sql->raw( $query );
		$db->setQuery( $sql );

		$jsProjects = $db->loadObjectList();

		if( count( $jsProjects ) <= 0 )
		{
			return null;
		}

		$json = FD::json();

		foreach( $jsProjects as $jsProject )
		{
			$parentId = $this->getEsProjectParendId( $jsProject );

			$esProject = FD::table( 'Cluster' );

			$params = array();
			$params['photo'] = array("albums" => true);
			$params['news'] = true;
			$params['discussions'] = true;
			$params['guestlimit'] = $jsProject->ticket;
			$params['allowmaybe'] = true;
			$params['allownotgoingguest'] = true;

			$esProject->parent_id 		= $parentId;
			$esProject->category_id		= $jsProject->escatid;
			$esProject->cluster_type 		= SOCIAL_TYPE_PROJECT;
			$esProject->creator_type 		= SOCIAL_TYPE_USER;
			$esProject->creator_uid		= $jsProject->creator;
			$esProject->title				= $jsProject->title;
			$esProject->description		= $jsProject->description;
			$esProject->alias 			= JFilterOutput::stringURLSafe( $jsProject->title );
			$esProject->state				= $jsProject->published;
			$esProject->created			= $jsProject->created;
			$esProject->params			= $json->encode( $params );
			$esProject->hits				= $jsProject->hits;
			$esProject->type 				= $jsProject->permission == 1 ? SOCIAL_PROJECT_TYPE_PRIVATE : SOCIAL_PROJECT_TYPE_PUBLIC;
			$esProject->key 				= ''; // TODO: check what is this key for

			// need to store the address, latitude and longitude
			$esProject->address = $jsProject->location;
			$esProject->latitude = $jsProject->latitude;
			$esProject->longitude = $jsProject->longitude;

			$state = $esProject->store();

			if( $state )
			{
				// insert into project_meta on start, end and timezone

				$meta = FD::table('ProjectMeta');
				$meta->cluster_id = $esProject->id;
				$meta->start = $jsProject->startdate;
				$meta->end = $jsProject->enddate;
				$meta->timezone = $jsProject->offset;
				$meta->store();

				// now we need to store the address into field_data as well.
				$esFieldId = $this->getFieldId('ADDRESS', $jsProject->escatid);

				if ($esFieldId) {

					//address
					$data = new stdClass();
					$data->datakey = 'address';
					$data->data = $jsProject->location;
					$data->raw = $jsProject->location;
					$this->addFieldData($esFieldId, $esProject->id, $data);

					//latitude
					$data = new stdClass();
					$data->datakey = 'latitude';
					$data->data = $jsProject->latitude;
					$data->raw = $jsProject->latitude;
					$this->addFieldData($esFieldId, $esProject->id, $data);

					//longitude
					$data = new stdClass();
					$data->datakey = 'longitude';
					$data->data = $jsProject->longitude;
					$data->raw = $jsProject->longitude;
					$this->addFieldData($esFieldId, $esProject->id, $data);

				}

				// TODO: Add project creation stream.
				if( $config->get( 'projects.stream.create' ) )
				{
					$stream				= FD::stream();
					$streamTemplate		= $stream->getTemplate();

					// Set the actor
					$streamTemplate->setActor( $jsProject->creator , SOCIAL_TYPE_USER );

					// Set the context
					$streamTemplate->setContext( $esProject->id , SOCIAL_TYPE_PROJECTS );

					$streamTemplate->setVerb( 'create' );
					$streamTemplate->setSiteWide();


					// Set the params to cache the group data
					$registry	= FD::registry();
					$registry->set( 'project' , $esProject );

					// Set the params to cache the group data
					$streamTemplate->setParams( $registry );

					$streamTemplate->setDate( $jsProject->created );

					$streamTemplate->setAccess('core.view');

					// Add stream template.
					$stream->add( $streamTemplate );
				}
				// end add stream

				$this->log( 'projects', $jsProject->id, $esProject->id );

				$this->info->setInfo( 'Project \'' . $jsProject->title . '\' has migrated succefully into EasySocial.' );
			}

		}//end foreach

		return $this->info;
	}

	private function processMembers()
	{
		$config = FD::config();
		$db 	= FD::db();
		$sql 	= $db->sql();

		$query = 'select a.*, c.`uid` as `esprojectid`, d.`creator` as `ownerid`, d.`created` as `projectcreatedate`';
		$query .= ' from `#__community_projects_members` as a';
		$query .= ' inner join `#__social_migrators` as c on a.`projectid` = c.`oid` and c.`element` = ' . $db->Quote( 'projects' ) . ' and c.`component` = ' . $db->Quote( 'com_community' );
		$query .= ' inner join `#__community_projects` as d on a.`projectid` = d.`id`';
		$query .= ' where not exists ( ';
		$query .= '		select b.`id` from `#__social_migrators` as b';
		$query .= ' 			where a.`memberid` = b.`oid` and b.`element` = concat_ws(' . $db->Quote('.') . ',' . $db->Quote( 'projectmembers' ) . ', a.`projectid` ) and b.`component` = ' . $db->Quote( $this->name );
		$query .= ' )';
		$query .= ' ORDER BY a.`projectid` ASC';
		$query .= ' LIMIT ' . $this->limit;

		$sql->raw( $query );
		$db->setQuery( $sql );

		$jsMembers = $db->loadObjectList();

		if( count( $jsMembers ) <= 0 )
		{
			return null;
		}

		foreach( $jsMembers as $jsMember )
		{

			if( $jsMember->status == '4' || $jsMember->status == '7' )
			{
				$this->log( 'projectmembers' . '.' . $jsMember->projectid , $jsMember->memberid, '0' );

				$this->info->setInfo( 'Member id \'' . $jsMember->memberid. '\' from Project \'' . $jsMember->projectid . '\' was blocked or not invited. Migration aborted for this member.' );

				return $this->info;
			}


			// lets check if the project join date is empty or not.
			// if yes, lets use project creation date.
			if (!$jsMember->created || $jsMember->created == '0000-00-00 00:00:00') {
				$jsMember->created = $jsMember->projectcreatedate;
			}

			$esMember = FD::table( 'ClusterNode' );

			$esMember->cluster_id	= $jsMember->esprojectid;
			$esMember->uid 			= $jsMember->memberid;
			$esMember->type 		= SOCIAL_TYPE_USER;
			$esMember->created		= $jsMember->created;
			$esMember->state		= $this->memberStateMapping($jsMember->status);
			$esMember->owner		= ( $jsMember->ownerid == $jsMember->memberid ) ? 1 : 0;
			$esMember->admin		= ( $jsMember->ownerid == $jsMember->memberid ) ? 1 : 0;
			$esMember->invited_by	= $jsMember->invited_by;

			$state = $esMember->store();

			if ($state && $jsMember->status == 1) {

        		$project = FD::project($jsMember->esprojectid);

		        // Load up the stream library
		        $stream = FD::stream();

		        // Get the stream template
		        $tpl = $stream->getTemplate();

		        // Set the verb
		        $tpl->setVerb('going');

		        // Set the context
		        // Since this is a "user" action, we set the context id to the guest node id, and the context type to guests
		        $tpl->setContext($esMember->id, 'guests');

		        // Set the privacy rule
		        $tpl->setAccess('core.view');

		        // Set the cluster
		        $tpl->setCluster($project->id, $project->cluster_type, $project->type);

		        // Set the actor
		        $tpl->setActor($esMember->uid, $esMember->type);

		        // set stream creation date
		        $tpl->setDate($jsMember->created);

		        // Add stream template.
		        $stream->add($tpl);

			}

			$this->log( 'projectmembers' . '.' . $jsMember->projectid , $jsMember->memberid, $esMember->id );

			$this->info->setInfo( 'Member id \'' . $jsMember->memberid. '\' from Project \'' . $jsMember->projectid . '\' has migrated succefully into EasySocial.' );

		}

		return $this->info;
	}

	private function memberStateMapping( $jsState )
	{
		$state = $this->stateMapping[$jsState];
		return $state;
	}


	private function getFieldId($fieldCode, $clusterId)
	{
		static $_cache = array();

		$db = FD::db();
		$sql = $db->sql();

		$key = $fieldCode . '_' . $clusterId;

		if (! isset($_cache[$key])) {

			$query = "select a.`id` from `#__social_fields` as a";
			$query .= "	inner join `#__social_fields_steps` as b on a.`step_id` = b.`id`";
			$query .= " where b.`type` = '".SOCIAL_TYPE_CLUSTERS."'";
			$query .= " and b.`uid` = '$clusterId'";
			$query .= " and a.`unique_key` = '$fieldCode'";
			$query .= " limit 1";

			$sql->raw($query);
			$db->setQuery($sql);

			$result = $db->loadResult();

			$_cache[$key] = $result;
		}

		return $_cache[$key];
	}

	private function addFieldData( $fieldId, $projectId, $data )
	{
		$fieldData = FD::table('FieldData');

		$fieldData->field_id = $fieldId;
		$fieldData->uid = $projectId;
		$fieldData->type = SOCIAL_TYPE_PROJECT;
		$fieldData->datakey = ($data->datakey == 'default' || $data->datakey == '' ) ? '' : $data->datakey;
		$fieldData->data = $data->data;
		$fieldData->raw = $data->raw;

		$fieldData->store();
	}

	private function getEsProjectParendId( $jsProject )
	{
		$db = FD::db();
		$sql = $db->sql();

		static $_cache = array();

		if ($jsProject->parent) {

			$pid = $jsProject->parent;

			if (! isset($_cache[$pid]) ) {

				$query = 'select b.`uid` from `#__social_migrators` as b';
				$query .= ' where b.`oid` = '. $db->Quote($pid);
				$query .= ' and b.`element` = ' . $db->Quote( 'projects' ) . ' and b.`component` = ' . $db->Quote( $this->name );

				$sql->raw($query);
				$db->setQuery($sql);

				$_cache[$pid] = $db->loadResult();
			}
			return $_cache[$pid];
		}

		return '0';
	}



	private function processProjectCategory()
	{

		$db 	= FD::db();
		$sql 	= $db->sql();

		$query = 'select a.*';
		$query .= ' from `#__community_projects_category` as a';
		$query .= ' where not exists ( ';
		$query .= '		select b.`id` from `#__social_migrators` as b';
		$query .= ' 			where a.`id` = b.`oid` and b.`element` = ' . $db->Quote( 'projectcategory' ) . ' and b.`component` = ' . $db->Quote( $this->name );
		$query .= ' )';
		$query .= ' ORDER BY a.`id` ASC';
		$query .= ' LIMIT ' . $this->limit;

		$sql->raw( $query );
		$db->setQuery( $sql );

		$jsProjectCats = $db->loadObjectList();

		if( count( $jsProjectCats ) <= 0 )
		{
			return null;
		}

		// TODO: get superadmin id
		$userModel 		= FD::model( 'Users' );
		$superadmins 	= $userModel->getSiteAdmins();
		$adminId 		= ( $superadmins ) ? $superadmins[0]->id : '42';

		foreach( $jsProjectCats as $jsProjectCat )
		{
			$esClusterCat = FD::table( 'ClusterCategory' );

			$esClusterCat->type 	= SOCIAL_TYPE_PROJECT;
			$esClusterCat->title 	= $jsProjectCat->name;
			$esClusterCat->alias 	= $jsProjectCat->name;
			$esClusterCat->description = $jsProjectCat->description;
			$esClusterCat->created 	= FD::date()->toMySQL();
			$esClusterCat->state 	= SOCIAL_STATE_PUBLISHED;
			$esClusterCat->uid 		= $adminId; // default to superadmin id

			$esClusterCat->store();

			// we no longer need to create the default steps items as the store function in cluster catgories will do the job.
			// $this->createDefaultStepItems( $esClusterCat->id );

			$this->log( 'projectcategory', $jsProjectCat->id, $esClusterCat->id );
			$this->info->setInfo( 'Project category \'' . $jsProjectCat->name . '\' is now migrated into EasySocial with id \'' . $esClusterCat->id . '\'.' );

		}// end foreach

		return $this->info;


	}

	private function createDefaultStepItems( $projectId )
	{
		// Read the default profile json file first.
		$path 		= SOCIAL_ADMIN_DEFAULTS . '/fields/project.json';

		$contents	= JFile::read( $path );

		$json 		= FD::json();
		$defaults 	= $json->decode( $contents );

		$newStepIds = array();

		// Let's go through each of the default items.
		foreach( $defaults as $step )
		{
			// Create default step for this profile.
			$stepTable 				= FD::table( 'FieldStep' );
			$stepTable->bind( $step );

			// always set this to yes.
			// $stepTable->visible_display = 1;

			// Map the correct uid and type.
			$stepTable->uid 		= $projectId;
			$stepTable->type 		= SOCIAL_TYPE_CLUSTERS;

			$stepTable->state 					= SOCIAL_STATE_PUBLISHED;
			$stepTable->sequence 				= 1;
			$stepTable->visible_registration 	= SOCIAL_STATE_PUBLISHED;
			$stepTable->visible_edit 			= SOCIAL_STATE_PUBLISHED;
			$stepTable->visible_display 		= SOCIAL_STATE_PUBLISHED;

			// Try to store the default steps.
			$state 			= $stepTable->store();

			$newStepIds[] 	= $stepTable->id;

			// Now we need to create all the fields that are in the current step
			if( $step->fields && $state )
			{

				foreach( $step->fields as $field )
				{
					$appTable 		= FD::table( 'App' );
					$appTable->loadByElement( $field->element , SOCIAL_TYPE_PROJECT , SOCIAL_APPS_TYPE_FIELDS );

					$fieldTable		= FD::table( 'Field' );


					$fieldTable->bind( $field );

					// Ensure that the main items are being JText correctly.
					$fieldTable->title 			= $field->title;
					$fieldTable->description	= $field->description;
					$fieldTable->default 		= isset( $field->default ) ? $field->default : '';

					// Set the app id.
					$fieldTable->app_id 	= $appTable->id;

					// Set the step.
					$fieldTable->step_id 	= $stepTable->id;

					// Set this to be published by default.
					$fieldTable->state 		= isset( $field->state ) ? $field->state : SOCIAL_STATE_PUBLISHED;

					// Set this to be searchable by default.
					$fieldTable->searchable = isset( $field->searchable ) ? $field->searchable : SOCIAL_STATE_UNPUBLISHED;

					// Set this to be searchable by default.
					$fieldTable->required = isset( $field->required ) ? $field->required : SOCIAL_STATE_UNPUBLISHED;

					$fieldTable->display_title 			= 1;
					$fieldTable->display_description 	= 1;
					$fieldTable->visible_registration 	= 1;
					$fieldTable->visible_edit 			= 1;
					$fieldTable->visible_display 		= isset( $field->visible_display ) ? $field->visible_display : SOCIAL_STATE_PUBLISHED;

					if( $field->element == 'startend' ) {
						$field->params['allow_time'] = isset( $field->allow_time ) ? $field->allow_time : SOCIAL_STATE_UNPUBLISHED;
						$field->params['allow_timezone'] = isset( $field->allow_timezone ) ? $field->allow_timezone : SOCIAL_STATE_UNPUBLISHED;
					}

					// Check if the default items has a params.
					if( isset( $field->params ) )
					{
						$fieldTable->params 	= FD::json()->encode( $field->params );
					}

					// Store the field item.
					$fieldTable->store();

					// set the unique key
					$fieldTable->checkUniqueKey();
					$fieldTable->store();

				}
			}
		}

		return $newStepIds;
	}



	private function getPhotoOrdering( $albumId )
	{
		$db 	= FD::db();
		$sql 	= $db->sql();

		$query = 'select max( `ordering` ) from `#__social_photos`';
		$query .= ' where `album_id` = ' . $db->Quote( $albumId );

		$sql->raw( $query );
		$db->setQuery( $sql );

		$ordering = $db->loadResult();

		return ( empty( $ordering ) ) ? '1' : $ordering + 1;
	}

	private function getFileMimeType( $file )
	{
		if ( function_exists("finfo_file") ) {
			$finfo 	= finfo_open( FILEINFO_MIME_TYPE ); // return mime type ala mimetype extension
			$mime 	= finfo_file( $finfo, $file );
			finfo_close( $finfo );
			return $mime;
		} else if ( function_exists("mime_content_type") ) {
			return mime_content_type( $file );
		} else {
			return JFile::getExt( $file );
		}
	}

	private function removeAdminSegment( $url = '' )
	{
		if( $url )
		{
			$url 	= '/' . ltrim( $url , '/' );
			$url 	= str_replace('/administrator/', '/', $url );
		}

		return $url;
	}

	public function log( $element, $oriId, $newId )
	{
		$tbl = FD::table( 'Migrators' );

		$tbl->oid 		= $oriId;
		$tbl->element 	= $element;
		$tbl->component = $this->name;
		$tbl->uid 		= $newId;
		$tbl->created 	= FD::date()->toMySQL();

		$tbl->store();
	}

}
