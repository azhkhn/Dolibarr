<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Tests;

use Splash\Client\Splash;
use Splash\Tests\Tools\ObjectsCase;
use Splash\Local\Core\ErrorParserTrait;

/**
 * Local Test Suite - Ensure Presence of Minimal Objects in Db
 */
class L00MinimalDataTest extends ObjectsCase
{
    use ErrorParserTrait;

    /**
     * Ensure at Least 2 Warehouses Exists
     *
     * @dataProvider sequencesProvider
     *
     * @param string $sequence
     *
     * @return void
     */
    public function testAtLeastTwoWarehouses(string $sequence)
    {
        global $db, $user;

        $this->loadLocalTestSequence($sequence);
        //====================================================================//
        // Include Object Dolibarr Class
        require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
        //====================================================================//
        // Count Number of Active Warehouses
        $sql = "SELECT e.rowid, e.ref FROM ".MAIN_DB_PREFIX."entrepot as e";
        $sql .= " WHERE e.entity IN (".getEntity('stock').")";
        $sql .= " AND e.statut = 1";
        $result = $db->query($sql);
        $this->assertNotEmpty($result);
        //====================================================================//
        // Create Missing Warehouses
        for($i=$db->num_rows($result); $i<2; $i++) {
            $warehouse = new \Entrepot($db);
            $warehouse->statut = 1;
            $warehouse->ref = "WH-".$i;
            $warehouse->libelle = "Warhouse ".$i;
            $warehouse->label = "Warhouse ".$i;
            $warehouse->description = "Warhouse ".$i;
            $warehouse->create($user);
            $this->assertTrue($this->catchDolibarrErrors($warehouse));
        }
    }

    /**
     * Ensure at Least 5 Objects of Each Types Exists
     *
     * @dataProvider ObjectTypesProvider
     *
     * @param string $sequence
     * @param string $objectType
     *
     * @return void
     */
    public function testAtLeastFiveObjects(string $sequence, $objectType)
    {
        global $db, $user;

        $this->loadLocalTestSequence($sequence);

        //====================================================================//
        //   Get Object List from Module
        $list = Splash::object($objectType)->objectsList();
        $this->assertIsArray($list);
        //====================================================================//
        //   Get Object Count
        $objectCount = $list["meta"]["current"];
        //====================================================================//
        // Create Missing Objects
        for($i=$objectCount; $i<1; $i++) {
            //====================================================================//
            //   Generate Dummy Object Data (Required Fields Only)
            $dummyData = $this->prepareForTesting($objectType);
            if (false == $dummyData) {
                $this->markTestSkipped($objectType." does not Allow Create");

                return;
            }
            //====================================================================//
            //   Create a New Object on Module
            $this->assertIsArray($dummyData);
            $objectId = Splash::object($objectType)->set(null, $dummyData);
            $this->assertIsString($objectId);
        }
    }

    /**
     * @param string $objectType
     *
     * @return bool
     */
    public function verifyTestIsAllowed(string $objectType)
    {
        $definition = Splash::object($objectType)->Description();

        $this->assertNotEmpty($definition);
        //====================================================================//
        //   Verify Create is Allowed
        if (!$definition["allow_push_created"]) {
            return false;
        }

        return true;
    }

    /**
     * @param string $objectType
     *
     * @return array|false
     */
    public function prepareForTesting(string $objectType)
    {
        //====================================================================//
        //   Verify Test is Required
        if (!$this->verifyTestIsAllowed($objectType)) {
            return false;
        }

        //====================================================================//
        // Read Required Fields & Prepare Dummy Data
        //====================================================================//
        $write = false;
        $fields = Splash::object($objectType)->Fields();
        foreach ($fields as $key => $field) {
            //====================================================================//
            // Skip Non Required Fields
            if (!$field->required) {
                unset($fields[$key]);
            }
            //====================================================================//
            // Check if Write Fields
            if ($field->write) {
                $write = true;
            }
        }

        //====================================================================//
        // If No Writable Fields
        if (!$write) {
            return false;
        }

        //====================================================================//
        // Lock New Objects To Avoid Action Commit
        Splash::object($objectType)->Lock();

        //====================================================================//
        // Clean Objects Committed Array
        Splash::$commited = array();

        return $this->fakeObjectData($fields);
    }
}
