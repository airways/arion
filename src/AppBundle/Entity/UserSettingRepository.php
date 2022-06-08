<?php

/*
 * @package ArionCRM
 * @author Isaac Raway <iraway@metasushi [dot] com>
 * @author Antoinette Smith <asmith@metasushi [dot] com>
 * @link http://arioncrm.com/
 * @copyright (c)2015-2022. MetaSushi, LLC. All rights reserved. Your use of this software in any way indicates agreement
 * to the software license available currenty at http://arioncrm.com/ 
 * This open source edition is released under GPL 3.0. available at https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace AppBundle\Entity;

/**
 * UserSettingRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserSettingRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * Helper to save setting
     */
    public function saveSetting(UserSetting $setting)
    {
        if(!$setting->getId()) {
           $this->getEntityManager()->persist($setting);
        }
        $this->getEntityManager()->flush();
        return $setting->getId();
    }
}