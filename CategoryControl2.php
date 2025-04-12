<?php

namespace MediaWiki\Extension\CategoryControl2\Hooks\Handlers;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserGroupManager;
use MediaWiki\Permissions\Hook\GetUserPermissionsErrorsHook;
use MediaWiki\Context\RequestContext;


$wgCategoryPermissions = array();


class UserPermissionsHandler implements GetUserPermissionsErrorsHook {
	public function onGetUserPermissionsErrors( $title, $user, $action, &$result ) {
		$result = NULL;
		$categories = $title->getParentCategories();

		if( is_array( $categories ) && count( $categories ) )
		{
				foreach( $categories AS $category => $index )
				{
						$category = substr( $category, (strpos($category, ":")+1));
						$allow = self::wfUserCategoryCan( $category, $wgUser, $action );

						if( !$allow )
								break;
				}
		}
		else
				$allow = TRUE;

		// Hack to display the proper error message.
		if( !$allow )
		{
				$result = FALSE;
				global $wgGroupPermissions;
				foreach( $wgGroupPermissions AS $group => $rights )
				{
						$wgGroupPermissions[$group]['read'] = FALSE;
				}
				return FALSE;
		}

		return TRUE;
	}

	function wfUserCategoryCan( $category, &$wgUser, $action )
	{
		global $wgCategoryPermissions;
		// If the requested category has no specified permissions, allow access.
		if( !array_key_exists( $category, $wgCategoryPermissions ) )
		{
			return TRUE;
		}
		// If the specified action has no specified permissions, allow access.
		if( !array_key_exists( $action, $wgCategoryPermissions[$category] ) && array_key_exists( '*', $wgCategoryPermissions[$category] ) )
		{		
			return TRUE;
		}
		$permission_lists = is_array( $wgCategoryPermissions[$category][$action] ) ? $wgCategoryPermissions[$category][$action] : $wgCategoryPermissions[$category]['*'];	   
		foreach( $permission_lists AS $list => $permissions )
		{
				$permission[$list] = TRUE;
				$user = RequestContext::getMain()->getUser();
				if( is_array( $permissions ) )
				{
						foreach( $permissions AS $group )
						{
								$permission[$list] = in_array( $group, $user->getEffectiveGroups() );
						}
				}
				else
				{
					$userGroupManager = MediaWikiServices::getInstance()->getUserGroupManager();
					$groups = $userGroupManager->getUserEffectiveGroups($user);
					$permission[$list] = in_array( $permissions, $groups );	
				}
		}
		return in_array( TRUE, $permission );
	}
}