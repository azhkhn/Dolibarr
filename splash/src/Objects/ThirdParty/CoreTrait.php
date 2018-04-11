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

namespace Splash\Local\Objects\ThirdParty;

use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Dolibarr ThirdParty Fields (Required) 
 */
trait CoreTrait {

    /**
    *   @abstract     Build Core Fields using FieldFactory
    */
    protected function buildCoreFields()   {
        global $langs,$conf;
        
        //====================================================================//
        // Company
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("name")
                ->Name($langs->trans("CompanyName"))
                ->isLogged()
                ->Description($langs->trans("CompanyName"))
                ->MicroData("http://schema.org/Organization","legalName")      
                ->isRequired()
                ->isListed();

        //====================================================================//
        // Firstname
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("firstname")
                ->Name($langs->trans("Firstname"))
                ->isLogged()
                ->MicroData("http://schema.org/Person","familyName")
                ->Association("firstname","lastname");        
        
        //====================================================================//
        // Lastname
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("lastname")
                ->Name($langs->trans("Lastname"))
                ->isLogged()
                ->MicroData("http://schema.org/Person","givenName")
                ->Association("firstname","lastname");        
                
        //====================================================================//
        // Reference
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("code_client")
                ->Name($langs->trans("CustomerCode"))
                ->Description($langs->trans("CustomerCodeDesc"))
                ->isListed()
                ->MicroData("http://schema.org/Organization","alternateName");
        //====================================================================//
        // Set as Read Only when Auto-Generated by Dolibarr        
        if ($conf->global->SOCIETE_CODECLIENT_ADDON != "mod_codeproduct_leopard") {
             $this->FieldsFactory()->isReadOnly();
        }  
        
    }    

    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    protected function getCoreFields($Key,$FieldName)
    {
        //====================================================================//
        // Read Company FullName => Firstname, Lastname - Compagny
        $fullname = $this->decodeFullName($this->Object->name);
        
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Fullname Readings
            case 'name':
            case 'firstname':
            case 'lastname':
                $this->Out[$FieldName] = $fullname[$FieldName];
                break;            
            
            //====================================================================//
            // Direct Readings
            case 'code_client':
                $this->getSimple($FieldName);
                break;
            default:
                return;
        }
        
        unset($this->In[$Key]);
    }
    
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    protected function setCoreFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Fullname Writtings
            case 'name':
            case 'firstname':
            case 'lastname':
                $this->$FieldName = $Data;
                break;

            //====================================================================//
            // Direct Writtings
            case 'code_client':
                $this->setSimple($FieldName, $Data);
                break;                    
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
    //====================================================================//
    // Class Tooling Functions
    //====================================================================//
    
    /**
    *   @abstract   Encode Full Name String using Firstname, Lastname & Compagny Name
    *   @param      string      $Firstname      Contact Firstname
    *   @param      string      $Lastname       Contact Lasttname
    *   @param      string      $Company        Contact Company
    *   @return     string                      Contact Full Name
    */    
    private static function encodeFullName($Firstname,$Lastname,$Company=Null)
    {
        //====================================================================//
        // Clean Input Data
        $FullName   = preg_replace('/[-,]/', '', trim($Firstname));
        $last       = preg_replace('/[-,]/', '', trim($Lastname));
        $comp       = preg_replace('/[-,]/', '', trim($Company));
        
//        $FullName = preg_replace('/[^A-Za-z0-9\-]/', '', trim($Firstname));
//        $last = preg_replace('/[^A-Za-z0-9\-]/', '', trim($Lastname));
//        $comp = preg_replace('/[^A-Za-z0-9\-]/', '', trim($Company));
        //====================================================================//
        // Encode Full Name
        if ( !empty($last) ) {
            $FullName .= ", ".$last;
        }
        if ( !empty($comp) ) {
            $FullName .= " - ".$comp;
        }
        return $FullName;
    }   
    
    /**
    *   @abstract   Decode Firstname, Lastname & Compagny Name using Full Name String 
    *   @param      string      $FullName      Contact Full Name
    *   @return     array                      Contact Firstname, Lastname & Compagny Name
    */    
    private static function decodeFullName($FullName=Null)
    {
        //====================================================================//
        // Safety Checks 
        if ( empty($FullName) ) {
            return Null;
        }
        
        //====================================================================//
        // Init
        $result = array('name' => "", 'lastname' => "",'firstname' => ""  );
        
        //====================================================================//
        // Detect Single Company Name
        if ( (strpos($FullName,' - ') == FALSE) && (strpos($FullName,', ') == FALSE) ) {
            $result['name']  =   $FullName;
            return $result;
        }
        //====================================================================//
        // Detect Compagny Name
        if ( ( $pos = strpos($FullName,' - ') ) != FALSE)
        {
            $result['name']     =   substr($FullName,$pos + 3);
            $FullName           =   substr($FullName,0, $pos);
        }
        //====================================================================//
        // Detect Last Name
        if ( ( $pos = strpos($FullName,', ') ) != FALSE)
        {
            $result['lastname'] =   substr($FullName,$pos + 2);
            $FullName           =   substr($FullName,0, $pos);
        }
        $result['firstname']         =   $FullName;
        return $result;
    }      
    
    /**
    *   @abstract   Check FullName Array and update if needed 
    */    
    protected function updateFullName() 
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__,__FUNCTION__); 
        //====================================================================//
        // Get Current Values if Not Written
        $CurrentName = $this->decodeFullName($this->Object->name);
        if ( !isset($this->firstname) && !empty($CurrentName["firstname"])) {
            $this->firstname = $CurrentName["firstname"];
        }         
        if ( !isset($this->lastname) && !empty($CurrentName["lastname"])) {
            $this->lastname = $CurrentName["lastname"];
        }         
        if ( !isset($this->name) && !empty($CurrentName["name"])) {
            $this->name = $CurrentName["name"];
        }         
        //====================================================================//
        // No First or Last Name
        if (empty($this->firstname) && empty($this->lastname)) {
            $this->setSimple("name", $this->name);
            return;
        } 
        //====================================================================//
        // Encode Full Name String
        $encodedFullName = $this->encodeFullName($this->firstname,$this->lastname,$this->name);
        $this->setSimple("name", $encodedFullName);
    }
    
}
