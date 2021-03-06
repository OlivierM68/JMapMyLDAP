<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  Adapter
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Helper class for SHAdapter.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Adapter
 * @since       2.1
 */
abstract class SHAdapterHelper
{
	/**
	 * Commits the changes to the adapter and parses the result.
	 * If any errors occurred then optionally log them and throw an exception.
	 *
	 * @param   SHAdapter  $adapter  Adapter.
	 * @param   boolean    $log      Log any errors directly to SHLog.
	 * @param   boolean    $throw    Throws an exception on error OR return array on error.
	 *
	 * @return  true|SHAdapterResponseCommits
	 *
	 * @since   2.1
	 */
	public static function commitChanges($adapter, $log = false, $throw = true)
	{
		$results = $adapter->commitChanges();
		$adapterName = $adapter->getName();

		if ($log)
		{
			// Lets log all the commits
			foreach ($results->getCommits() as $commit)
			{
				if ($commit->status === JLog::INFO)
				{
					SHLog::addAdapter($adapter, $commit->getSummary(), 10634, JLog::INFO);
				}
				else
				{
					SHLog::addAdapter($adapter, $commit->getSummary(), 10636, JLog::ERROR);
					SHLog::add($commit->exception, 10637, JLog::ERROR, $adapterName);
				}
			}
		}

		// Check if any of the commits failed
		if (!$results->status)
		{
			if ($throw)
			{
				throw new RuntimeException(JText::_('LIB_SHADAPTERHELPER_ERR_10638'), 10638);
			}
			else
			{
				return $results;
			}
		}

		return true;
	}
}
