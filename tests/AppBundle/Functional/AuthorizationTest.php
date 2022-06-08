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

class AuthorizationTest extends KernelTestCase
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

        $items = static::$kernel->getContainer()->get('itemsService');

        $clientA = $items->create('clients', []);
        $clientA->item->setValue('name', 'Client A');
        $clientA->item->save(0, $user->getId(), $restrictedUserOwnerItemType, $restrictedUserOwnerItemId);

        $clientB = $items->create('clients', []);
        $clientB->item->setValue('name', 'Client B');
        $clientB->item->save(0, $user->getId(), $restrictedUserOwnerItemType, $restrictedUserOwnerItemId);

        $contacts = [
            [$clientA->item->getId(), 'isaac+contactA@example.com', 'Contact A', 'A'],
            [$clientB->item->getId(), 'isaac+contactB@example.com', 'Contact B', 'B'],
        ];

        foreach($contacts as $data)
        {
            $contact = $items->create('contacts', []);
            if(get_class($contact) != 'AppBundle\Service\ItemsCreateResult') {
                exit($this->colorize('GOT '.get_class($contact).' BUT NOT EXPECTING IT').PHP_EOL);
            }

            sleep(1);

/*
    public function edit($itemTypeName, $allFilters, $sort, $itemId, $prev_ver, array $data, array $files, array $cmd, $sendNotifications=true, $overrideUserId=false)
        */
            $editResult = $items->edit('contacts', [], '', $contact->item->getId(), $contact->item->getVer(), 
                         [$contact->item->getId() =>
                                ['email' => [[$data[1]]],
                                 'first_name' => $data[2],
                                 'last_name' => $data[3],
                                 'phone_number' => [['']],
                                 'client' => $data[0]]], [], [], false);
            if($editResult->error) {
                exit($this->colorize('SERVICE ERROR '.json_encode($editResult)).PHP_EOL);
            }
            $this->changeUserPassword($data[1], 'Test1234!');

        }


        $tickets = [
            [$clientA->item->getId(), 'Ticket A'],
            [$clientB->item->getId(), 'Ticket B'],
        ];

        foreach($tickets as $data)
        {
            $ticket = $items->create('tickets', []);
            if(get_class($contact) != 'AppBundle\Service\ItemsCreateResult') {
                exit($this->colorize('GOT '.get_class($contact).' BUT NOT EXPECTING IT').PHP_EOL);
            }

            sleep(1);
            $editResult = $items->edit('tickets', [], '', $ticket->item->getId(), $ticket->item->getVer(), 
                         [$ticket->item->getId() =>
                                ['summary' => $data[1],
                                 'description' => '',
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

            $this->tickets[$data[1]] = $ticket->item->getId();

        }
        
        
    }

    public function testContactAItemVisibility()
    {
        $auth = static::$kernel->getContainer()->get('authService');
        
        // Login as the first client user
        $auth->logout();
        list($user, $restrictedUserOwnerItemType, $restrictedUserOwnerItemId)
            = $this->loginUser('isaac+contactA@example.com', 'Test1234!');

        $this->assertEquals(true, $auth->isLoggedIn(), 'Auth thinks we are logged in');

        $users = static::$kernel->getContainer()->get('userRepository');
        $items = static::$kernel->getContainer()->get('itemsService');
        $items->clearCache();   // Clear cache to force queries to run when they would be
                                // skipped, causing incorrect results since the setup was
                                // done as admin user

        $user = $auth->getUser();

        // Make sure we are logged in as the user we expected
        $checkUser = $users->loadUserByUsername('isaac+contactA@example.com');
        $this->assertNotEquals(0, $checkUser->getId(), 'UserID is not zero');
        $this->assertEquals($checkUser->getId(), $auth->getUserId(), 'Logged in as expected user');

        // Make sure the user is restricted
        $this->assertNotEquals(0, $restrictedUserOwnerItemType, 'Logged in user is owned by something 1/2');
        $this->assertNotEquals(0, $restrictedUserOwnerItemId, 'Logged in user is owned by something 2/2');
        
        // Email address saved is always made lowercase
        $this->assertEquals('isaac+contacta@example.com', $user->getEmail(), 'User email is correct and lowercase');

        // Contact A can only see Ticket A
        //public function view($itemType, $allFilters, $search="", $sort, $currentItemId=0, $viewType=false, $refresh=false)
        $viewResult = $items->view('tickets', [], '', '', 0, 'edit', false);
        $this->assertEquals(1, count($viewResult->listing->items), 'One ticket in view result');
        $this->assertEquals('Ticket A', $viewResult->listing->items[0]->summary, 'Ticket is Ticket A');

        // Contact A cannot request Ticket B
        $this->setExpectedException(\InvalidArgumentException::class);
        $ticketGetResult = $items->get($this->tickets['Ticket B']);
        // $this->assertNotEquals(0, $ticketGetResult->error, 'Error code is returned');
        // $this->assertNotEquals('', $ticketGetResult->error_message, 'Error message is returned');

    }

    public function testContactBItemVisibility()
    {
        $auth = static::$kernel->getContainer()->get('authService');
        
        // Login as the first client user
        $auth->logout();
        list($user, $restrictedUserOwnerItemType, $restrictedUserOwnerItemId)
            = $this->loginUser('isaac+contactB@example.com', 'Test1234!');

        $this->assertEquals(true, $auth->isLoggedIn(), 'Auth thinks we are logged in');

        $users = static::$kernel->getContainer()->get('userRepository');
        $items = static::$kernel->getContainer()->get('itemsService');
        $items->clearCache();   // Clear cache to force queries to run when they would be
                                // skipped, causing incorrect results since the setup was
                                // done as admin user

        $user = $auth->getUser();

        // Make sure we are logged in as the user we expected
        $checkUser = $users->loadUserByUsername('isaac+contactB@example.com');
        $this->assertNotEquals(0, $checkUser->getId(), 'UserID is not zero');
        $this->assertEquals($checkUser->getId(), $auth->getUserId(), 'Logged in as expected user');

        // Make sure the user is restricted
        $this->assertNotEquals(0, $restrictedUserOwnerItemType, 'Logged in user is owned by something 1/2');
        $this->assertNotEquals(0, $restrictedUserOwnerItemId, 'Logged in user is owned by something 2/2');
        
        // Email address saved is always made lowercase
        $this->assertEquals('isaac+contactb@example.com', $user->getEmail(), 'User email is correct and lowercase');

        // Contact B can only see Ticket B
        $viewResult = $items->view('tickets', [], '', '', 0, 'edit', false);
        $this->assertEquals(1, count($viewResult->listing->items), 'One ticket in view result');
        $this->assertEquals('Ticket B', $viewResult->listing->items[0]->summary, 'Ticket is Ticket B');

        // Contact B cannot request Ticket A
        $this->setExpectedException(\InvalidArgumentException::class);
        $ticketGetResult = $items->get($this->tickets['Ticket A']);
        // $this->assertNotEquals(0, $ticketGetResult->error, 'Error code is returned');
        // $this->assertNotEquals('', $ticketGetResult->error_message, 'Error message is returned');

    }

    public function testAdminItemVisibility()
    {
        $auth = static::$kernel->getContainer()->get('authService');
        
        // Login as the first client user
        $auth->logout();
        list($user, $restrictedUserOwnerItemType, $restrictedUserOwnerItemId)
            = $this->loginUser('IRAWAY@metasushi [dot] com', 'Test1234!');

        $this->assertEquals(true, $auth->isLoggedIn(), 'Auth thinks we are logged in');

        $users = static::$kernel->getContainer()->get('userRepository');
        $items = static::$kernel->getContainer()->get('itemsService');
        $items->clearCache();   // Clear cache to force queries to run when they would be
                                // skipped, causing incorrect results since above tests would
                                // not be in the same context as this test

        $user = $auth->getUser();

        // Make sure we are logged in as the user we expected
        $checkUser = $users->loadUserByUsername('iraway@metasushi [dot] com');
        $this->assertNotEquals(0, $checkUser->getId(), 'UserID is not zero');
        $this->assertEquals($checkUser->getId(), $auth->getUserId(), 'Logged in as expected user');

        // Make sure the user is NOT restricted
        $this->assertEquals(0, $restrictedUserOwnerItemType, 'Logged in user is not owned by anything 1/2');
        $this->assertEquals(0, $restrictedUserOwnerItemId, 'Logged in user is not owned by anything 2/2');
        
        // Email address saved is always made lowercase
        $this->assertEquals('iraway@metasushi [dot] com', $user->getEmail(), 'User email is correct and lowercase');

        // Admin can see both tickets
        $viewResult = $items->view('tickets', [], '', '', 0, 'edit', false);
        $this->assertEquals(2, count($viewResult->listing->items), 'Both ticket in view result');
        
        $ticketSummaries = [
            $viewResult->listing->items[0]->summary,
            $viewResult->listing->items[1]->summary,
        ];

        $ticketGetResult = $items->get($this->tickets['Ticket A']);
        $ticketGetResult = $items->get($this->tickets['Ticket B']);

    }


    public function tearDown()
    {
        parent::tearDown();

        
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

    private function changeUserPassword($username, $password)
    {
        $users = static::$kernel->getContainer()->get('userRepository');
        $auth = static::$kernel->getContainer()->get('authService');
        $user = $users->loadUserByUsername($username);
        if(!$user) {
            exit('CANNOT GET TEST USER '.$username);
        }
        return $auth->forceChangePassword($user, 'Test1234!');
    }

    private function colorize($str, $red=true)
    {
        $color = $red ? '41;37' : '43;30';

        return "\x1B[{$color}m{$str}\x1B[0m";
    }
}
