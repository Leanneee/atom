<?php

/*
 * This file is part of the Access to Memory (AtoM) software.
 *
 * Access to Memory (AtoM) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Access to Memory (AtoM) is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Access to Memory (AtoM).  If not, see <http://www.gnu.org/licenses/>.
 */

class ApiInformationObjectsWorksStatusAction extends QubitApiAction
{
  protected function get($request)
  {
    $this->io = QubitInformationObject::getById($request->id);

    if (null === $this->io)
    {
      throw new QubitApi404Exception('Information object not found');
    }

    if ($this->io->levelOfDescriptionId != sfConfig::get('app_drmc_lod_artwork_record_id'))
    {
      throw new QubitApiException('Status not available for this level of description');
    }

    if (null === $this->io->identifier)
    {
      throw new QubitApiException('TMS object ID not found');
    }

    $results = array();

    // Get last modified date from TMS and database
    $tmsDate = arFetchTms::getLastModifiedCheckDate($this->io->identifier);
    $atomDate = $this->io->getPropertyByName('LastModifiedCheckDate')->value;

    if (isset($tmsDate) && isset($atomDate))
    {
      $results['updated'] = $tmsDate === $atomDate;
    }

    // Check if it's being updated ('updating_artwork' key in cache)
    // This requires Symfony using sfMemcacheCache to work with the Gearman worker
    try
    {
      $cache = QubitCache::getInstance();
      if ($this->io->id == $cache->get('updating_artwork'))
      {
        $results['updating'] = true;
      }
      else
      {
        $results['updating'] = false;
      }
    }
    catch (Exception $e)
    {

    }

    return $results;
  }
}
