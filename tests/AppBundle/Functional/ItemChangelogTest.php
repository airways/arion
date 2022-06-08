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

namespace Tests\AppBundle\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ItemChangelogTest extends KernelTestCase
{
    private $em;
    private $tickets;

    public function setUp()
    {
        self::bootKernel();

        $this->em = static::$kernel->getContainer()->get('doctrine')->getManager();
        
        // tearUPPP
        $this->em->getConnection()->query('DELETE FROM items WHERE item_type_id != 30');
        $this->em->getConnection()->query('DELETE FROM item_values WHERE item_id NOT IN (SELECT id FROM items)');
        $this->em->getConnection()->query('DELETE FROM users WHERE email LIKE "isaac+contact%"');

        // Login as admin to create test data
        list($user, $restrictedUserOwnerItemType, $restrictedUserOwnerItemId)
            = $this->loginUser('iraway@metasushi [dot] com', 'Test1234!');

        
        
        
    }

    public function testContactAItemVisibility()
    {

        $original = <<<END
- Goal: Filter the Project dropdown enum by the currently selected Client
- Should be implemented in a generic way so that any dropdown pointed at an item type that has a Relationship field pointed to another dropdown on the form will be filtered by its selection.
END;

        $new = <<<END
- Goal: Filter the Project dropdown enum by the currently selected Client
- Should be implemented in a generic way so that any dropdown pointed at an item type that has a Relationship field pointed to another dropdown on the form will be filtered by its selection.
END;

        $items = static::$kernel->getContainer()->get('itemsService');

        $ticket = $items->create('tickets', []);
        if(get_class($ticket) != 'AppBundle\Service\ItemsCreateResult') {
            exit($this->colorize('GOT '.get_class($contact).' BUT NOT EXPECTING IT').PHP_EOL);
        }

        sleep(1);
        $data = $ticket->item->getValues();
        $editResult = $items->edit('tickets', [], $ticket->item->getId(), $ticket->item->getVer(), 
                     [$ticket->item->getId() =>
                            ['summary' => $data[1],
                             'description' => $original,
                             'comments' => [['']],
                             'notes' => [['']],
                             'client' => $data[0],
                             'project' => 0,
                             'status' => 'open',
                             'assigned_to' => 0,
                             'priority' => 1]],
                             [$ticket->item->getId() =>
                                ['files' => []]],
                             [], false);
        if($editResult->error) {
            exit($this->colorize('SERVICE ERROR '.json_encode($editResult)).PHP_EOL);
        }

        
        $data = $editResult->item->getValues();
        $data['description'] = $new;

        $editResult = $items->edit('tickets', [], $ticket->item->getId(), $ticket->item->getVer(), 
             [$ticket->item->getId() => $data],
                     [$ticket->item->getId() =>
                        ['files' => []]],
                     [], false);
        if($editResult->error) {
            exit($this->colorize('SERVICE ERROR '.json_encode($editResult)).PHP_EOL);
        }

        // TODO check changelog, make sure description is not marked as changed

    }

    private function colorize($str, $red=true)
    {
        $color = $red ? '41;37' : '43;30';

        return "\x1B[{$color}m{$str}\x1B[0m";
    }

    private function loginUser($username, $password)
    {
        $auth = static::$kernel->getContainer()->get('authService');
        $user = $auth->loginAsUser($username, $password);
        if(!$user) {
            exit('CANNOT LOGIN AS TEST USER '.$username.PHP_EOL);
        }

        list($restrictedUserOwnerItemType, $restrictedUserOwnerItemId) = $auth->getRestrictedUserKey($user->getAccountId(), $user->getId());

        return [$user, $restrictedUserOwnerItemType, $restrictedUserOwnerItemId];
    }
}

