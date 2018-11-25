<?php
/**
 * This file is part of SplashSync Project.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *  @author    Splash Sync <www.splashsync.com>
 *  @copyright 2015-2017 Splash Sync
 *  @license   GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 *
 **/

namespace Splash\Local\Objects\Product;

use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Dolibarr Product CRUD Functions
 */
trait CRUDTrait
{
    
    /**
     * @abstract    Load Request Object
     * @param       string  $Id               Object id
     * @return      mixed
     */
    public function load($Id)
    {
        global $db;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Init Object
        $Object = new \Product($db);
        //====================================================================//
        // Fatch Object
        if ($Object->fetch($Id) != 1) {
            $this->catchDolibarrErrors($Object);
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, " Unable to load Product (" . $Id . ").");
        }
        //====================================================================//
        // Check Object Entity Access (MultiCompany)
        if (!Splash::local()->isMultiCompanyAllowed($Object)) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, " Unable to load Product (" . $Id . ").");
        }
        return $Object;
    }

    /**
     * @abstract    Create Request Object
     *
     * @param       array   $List         Given Object Data
     *
     * @return      object     New Object
     */
    public function create()
    {
        global $db, $user, $langs;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Check Product Ref is given
        if (empty($this->in["ref"])) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, $langs->trans("ProductRef"));
        }
        //====================================================================//
        // Check Product Label is given
        if (empty($this->in["label"])) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, $langs->trans("ProductLabel"));
        }
        //====================================================================//
        // LOAD USER FROM DATABASE
        if (empty($user->login)) {
            return Splash::log()->err("ErrLocalUserMissing", __CLASS__, __FUNCTION__);
        }
        
        //====================================================================//
        // Init Object
        $this->object = new \Product($db);
        //====================================================================//
        // Pre-Setup of Dolibarr infos
        $this->setSimple("ref", $this->in["ref"]);
        $this->setMultilang("label", $this->in["label"]);
        //====================================================================//
        // Required For Dolibarr Below 3.6
        $this->object->type        = 0;
        //====================================================================//
        // Required For Dolibarr BarCode Module
        $this->object->barcode     = -1;
        //====================================================================//
        // LOAD USER FROM DATABASE
        Splash::local()->LoadLocalUser();
        if (empty($user->login)) {
            return Splash::log()->err("ErrLocalUserMissing", __CLASS__, __FUNCTION__);
        }
        //====================================================================//
        // Create Object In Database
        if ($this->object->create($user) <= 0) {
            $this->catchDolibarrErrors();
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to create new Product. ");
        }
        
        return $this->object;
    }
    
    /**
     * @abstract    Update Request Object
     *
     * @param       array   $Needed         Is This Update Needed
     *
     * @return      string      Object Id
     */
    public function update($Needed)
    {
        global $user;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        if (!$Needed) {
            Splash::log()->deb("Product Update not Needed");
            return (int) $this->object->id;
        }
        //====================================================================//
        // LOAD USER FROM DATABASE
        Splash::local()->LoadLocalUser();
        if (empty($user->login)) {
            return Splash::log()->err("ErrLocalUserMissing", __CLASS__, __FUNCTION__);
        }
        //====================================================================//
        // Update Product Object
        if ($this->object->update($this->object->id, $user) <= 0) {
            $this->catchDolibarrErrors();
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to Update Product (" . $this->object->id . ")"
            ) ;
        }
        //====================================================================//
        // Update Object Extra Fields
        if ($this->object->insertExtraFields()  <= 0) {
            $this->catchDolibarrErrors();
        }
        return (int) $this->object->id;
    }
    
    /**
     * @abstract    Delete requested Object
     *
     * @param       int     $Id     Object Id.  If NULL, Object needs to be created.
     *
     * @return      bool
     */
    public function delete($Id = null)
    {
        global $db,$user;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Load Object
        $Object = new \Product($db);
        //====================================================================//
        // LOAD USER FROM DATABASE
        Splash::local()->LoadLocalUser();
        if (empty($user->login)) {
            return Splash::log()->err("ErrLocalUserMissing", __CLASS__, __FUNCTION__);
        }
        //====================================================================//
        // Set Object Id, fetch not needed
        $Object->id = $Id;
        //====================================================================//
        // Check Object Entity Access (MultiCompany)
        unset($Object->entity);
        if (!Splash::local()->isMultiCompanyAllowed($Object)) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to Delete Product (" . $Id . ")."
            );
        }
        //====================================================================//
        // Delete Object
        $Arg1 = ( Splash::local()->dolVersionCmp("6.0.0") > 0 ) ? $user : 0;
        if ($Object->delete($Arg1) <= 0) {
            return $this->catchDolibarrErrors($Object);
        }
        return true;
    }
}
