<?php

class User extends CI_Model
{
    /**
     * Indicates whether anonymous users can view the content of a certain column. Columns not in
     * this array shouldn't be visible at all. Besides these, viewing brand preferences also does
     * not require being logged in.
     */
    const visibility = array(
        'username'      => true,
        'email'         => false,
        'firstName'     => false,
        'lastName'        => false,
        'gender'        => true,
        'birthdate'        => true,
        'description'    => true,
        'minAgePref'    => true,
        'maxAgePref'    => true,
        'genderPref'    => true,
        'personalityI'    => true,
        'personalityN'    => true,
        'personalityT'    => true,
        'personalityJ'    => true,
        'preferenceI'    => true,
        'preferenceN'    => true,
        'preferenceT'    => true,
        'preferenceJ'    => true,
        'picture'        => false
    );
    
    /**
     * Returns the columns visibile to the currently active user.
     */
    private function visibleColumns()
    {
        $login = $this->authentication->userLoggedIn();
        if($login)
        {
            return array_keys(visibility);
        }
        else
        {
            $result = array();
            foreach(visibility as $col => $visible)
            {
                if($visible)
                {
                    $result[] = $col;
                }
            }
            return $result;
        }
    }
    
    /**
     * Loads the preferred brands of a user.
     * 
     * @param int $userId The ID of
     * 
     * @return array(string) The preferred brands of this user. 
     */
    private function loadUserBrands($userId)
    {
        $result = $this->db->select('brandName')
                           ->from('UserBrands, Brands')
                           ->where('UserBrands.brandName == Brands.brandName')
                           ->where(array('userId' => $userId))
                           ->get()->result_array();
        
        if(count($result) == 0)
        {
            //TODO
            throw new Exception('User not found or no brands associated.');
        }
        
        $brandNames = array();
        foreach($result as $row)
        {
            $brandNames[] = $row['brandName'];
        }
        return $brandNames;
    }
    
    /**
     * Loads the properties of a user visible to the current user. These properties are the values
     * of columns in the Users-table plus an entry 'brands' containing an array of strings 
     * representing brand names preferred by the user.
     * 
     * @param int           $userId     The identifier of the user to load.
     * @param array(string) $properties The properties of the user to load. If null,
     *                                  all accessible properties are loaded.
     * 
     * @return array(string => mixed) An associative array of user properties and values.
     */
    public function load($userId, $properties = null)
    {
        // Determine which columns to select.
        $columns = $this->visibleColumns();
        if($properties !== null)
        {
            // Filter by wanted properties.
            $columns = array_intersect($columns, $properties);
        }
        
        $result = $this->db->select($columns)
                           ->from('Users')
                           ->where(array('userId' => $userId))
                           ->get()->result_array();
        
        if(count($result) == 0)
        {
            //TODO
            throw new Exception('User not found.');
        }
        
        $user = $result[0];
        
        // Add brands if wanted.
        if($properties === null || in_array('brands', $properties))
        {
            $user['brands'] = $this->loadUserBrands($userId);
        }
        
        return $user;
    }
    
    public function updateSelf($newprops)
    {
        // Determine which user is logged in.
        $userId = $this->authentication->currentUserId();
        
        if($userId === null)
        {
            //TODO
            throw new Exception('User is not logged in.');
        }
        
        //Start a transaction.
        $this->db->trans_start();
        
        // Values in Users table to change are all new properties that are also visible columns.
        $data = array_intersect($newprops, visibility);
        
        // Do update.
        $this->db->where(array('userId' => $userId))
                 ->update('Users', $data);
        
        // If requested, update brands.
        if(isset($newprops['brands']))
        {
            // First simply remove this user's brands.
            $this->db->delete('UserBrands', array('userId' => $userId));
            
            // Now insert new brands.
            $brandList = array();
            foreach($newprops['brands'] as $brand)
            {
                $brandList[] = array('userId'    => $userId,
                                     'brandName' => $brand);
            }
            $this->db->insert_batch('UserBrands', $brandList);
        }
                 
        // Complete transaction.
        $this->db->trans_complete();
    }
    
    /**
     * Create a new user.
     * 
     * @param array(string => mixed) $data     Values to be inserted into the Users table, except for
     *                                         the password hash. Data should be already validated!
     * @param string                 $password The user's password. Will be hashed by this function.
     * @param array(string)          $brands   List of preferred brands by this user. There should
     *                                         be at least one.
     *                                         
     * @return int The ID of the newly created user.
     */
    public function createUser($data, $password, $brands)
    {        
        //Start a transaction.
        $this->db->trans_start();
        
        // Insert new user into table.
        $this->db->insert('Users', $data);
        
        // Add preferred brands.
        $userId = $this->db->insert_id();
        $brandList = array();
        foreach($brands as $brand)
        {
            $brandList[] = array('userId'    => $userId,
                                 'brandName' => $brand);
        }
        $this->db->insert_batch('UserBrands', $brandList);
        
        // Complete transaction.
        $this->db->trans_complete();
        
        return $userId;
    }
    
    /**
     * Delete the currently active user.
     */
    public function deleteSelf()
    {
        // Determine which user is logged in, if any.
        $userId = $this->authentication->currentUserId();
        
        if($userId === null)
        {
            //TODO
            throw new Exception('User is not logged in.');
        }
        
        // Delete this user.
        $this->db->delete('Users', array('userId' => $userId));
    }
}