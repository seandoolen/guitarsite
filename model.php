<?php
    class DatabaseAdapter
    {
        private $DB; // Instance variable
        
        /*
         *  Constructor 
         *  DB name, guitar_site
         */
        public function __construct()
        {
            $db = 'mysql:dbname=guitar_site; host=127.0.0.1; charset=utf8';
            $user = 'root';
            $pass = '';
            try 
            {
                $this->DB = new PDO( $db, $user, $pass);
                $this->DB->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
            } 
            catch (PDOException $e)
            {
                echo "Connection to guitar_site failed!";
                exit();
            }
        }
    
        
        /*
         *  Returns all of the users from the 'users' table 
         *  
         *  users
         *  ID | username | hash
         *  
         *  @returns: the array of users
         */
        public function getAllUsers()
        {
            $stmt = $this->DB->prepare("SELECT * FROM users");
            $stmt->execute();
            $usrs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $usrs;
        }
        
        /*
         *  Returns all of the guitars from the 'guitars' table
         *
         *  guitars
         *  ID(integer) | brand(string) | name | price(float) | electric(int) 1 or 0
         *  
         *  @returns: all of the guitar items
         */
        public function getAllGuitars()
        {
            $stmt = $this->DB->prepare("SELECT * FROM guitars");
            $stmt->execute();
            $guitars = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $guitars;
        }
        
        /*
         *  This function creates a new user, it checks if the user already exists, if so,
         *  it fails, otherwise it hashes the user's password then adds the data to the database
         *
         *  @params:
         *      $usr: the username
         *      $pwd: the password
         *
         *  @returns:
         *      1 on successful user creation
         *      0 on failed user creation
         */
        public function createAccount($usr,$pwd)
        {
            $stmt = $this->DB->prepare("SELECT * FROM users WHERE user=:usr");
            $stmt->bindParam(":usr",$usr);
            $stmt->execute();
            $name = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($name) //user already exists
                return 0;
                else
                {
                    // Creating the new user
                    $id = $this->getUserAmount() + 1; // set the id
                    $hash = password_hash($pwd,PASSWORD_DEFAULT); // hash the password
                    $insert = $this->DB->prepare("INSERT INTO users VALUES ('$id','$usr','$hash')"); // Store
                    $insert->execute();
                    session_start();
                    $_SESSION['user'] = $usr;
                    return 1;
                }
        }
        
        
        
        /*
         *  Called on user login. Checks if a user exists in the database
         *  and if the password they typed is correct.
         *
         *  @params
         *      $usr: the username
         *      $pwd: the password
         *
         *  @returns
         *      0 on successful login
         *      1 on username not existing
         *      2 on incorrect password
         *
         */
        public function verifyCredentials($usr,$pwd)
        {
            $stmt = $this->DB->prepare("SELECT * FROM users WHERE user=:usr");
            $stmt->bindParam(":usr",$usr);
            $stmt->execute();
            $user = $stmt->fetch( PDO::FETCH_ASSOC );
            
            if(!$user) //user doesn't exist
               return 1;
            else if(!password_verify($pwd, $user['hash'])) //passwords don't match
               return 2;
            else
            {
               session_start();
               $_SESSION['user'] = $usr;
               return 0;
            }
        }
        
        /*
         *  Gets the amount of records in users
         *
         *  @params: none
         *
         *  @returns:
         *      the number of users
         */
        public function getUserAmount()
        {
            $count = $this->DB->prepare("SELECT * FROM users");
            $count->execute();
            return $count->rowCount();
        }
        
        /*
         *  Gets a guitar by the product ID
         *  
         */
        public function getGuitarById($id)
        {
            $stmt = $this->DB->prepare("SELECT * FROM guitars WHERE id=:id");
            $stmt->bindParam(":id",$id);
            $stmt->execute();
            $guitar = $stmt->fetchALL(PDO::FETCH_ASSOC);
            return $guitar;
        }
       
        /*
         *  Adds an item to the user's cart
         *  
         *  params:
         *      $user: the name of the user
         *      $prodID: the id of the product
         */
        public function addToCart($usr,$prodID)
        {
            $userID = $this->usernameToID($usr);
            echo $prodID;
            $stmt = $this->DB->prepare("INSERT INTO purchases VALUES($userID,:prodID)");
            $stmt->bindParam(":prodID", $prodID, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        
        /*
         *  Gets all of the items in a user's cart
         *  This function uses a JOIN
         *  
         *  @params
         *      $usr: the name of the user
         *      
         * @returns: a 2d array of items
         * 
         */
        public function getPurchases($usr)
        {
            $id = $this->usernameToID($usr);
            $stmt = $this->DB->prepare("SELECT guitars.brand, guitars.name, guitars.price 
                                        FROM purchases 
                                        JOIN guitars ON purchases.itemID = guitars.ID 
                                        WHERE purchases.userID=".$id);
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $items;
        }
        
        /*
         *  Empties all the items from the cart of a user
         *  
         *  @params
         *      $usr: the name of the user
         *      
         *  @returns: void
         */
        public function emptyCart($usr)
        {
            $userID = $this->usernameToID($usr);
            $stmt = $this->DB->prepare("DELETE FROM purchases WHERE userID=$userID");
            $stmt->execute();
        }
        
        /*
         *  Takes a username returns the ID
         *  Called by addToCart and emptyCart
         *  
         *  @params
         *      $usr: the name of the user
         *  
         *  @returns: the user's id
         *    
         */
        public function usernameToID($usr)
        {
            $stmt = $this->DB->prepare("SELECT ID FROM users WHERE user=:usr"); // Get the id
            $stmt->bindParam(":usr",$usr);
            $stmt->execute();
            $userID = $stmt->fetch(PDO::FETCH_ASSOC); // got the id
            $userID = $userID['ID'];
            return $userID;
        }
        
        
        /*TODO: Add more functions here*/
        
    } // end of DataBaseAdapter
    
    //The Object
    $theDBA = new DatabaseAdapter();
    
    //$theDBA->getPurchases("Sean");
    
    //$theDBA->addToCart("Sean",1);
    //$theDBA->addToCart("red",3);
    
    //$theDBA->emptyCart("Sean");
    
    //$arr = $theDBA->getGuitarById(1);
    //print_r($arr);
    
    //Testcases down here COMMENT WHEN DONE TESTING!
    
   /* $arr = $theDBA->getUserPurchases(1);
    print_r($arr);
    $arr = $theDBA->getAllUsers();
    print_r($arr);*/
   /* $arr = $theDBA->getAllGuitars();
    print_r($arr);*/
    
   /* $rt = $theDBA->createAccount("orange", "orange");
    echo $rt;
    
    $rt = $theDBA->createAccount("Steve", "Steve");
    echo $rt;
    
    $rt = $theDBA->verifyCredentials("Nope", "Steve");
    echo $rt;
    $rt = $theDBA->verifyCredentials("Steve", "Nope");
    echo $rt;
    $rt = $theDBA->verifyCredentials("orange", "orange");
    echo $rt;*/
    
   // $theDBA->getPurchases("Steve");
    
    //$theDBA->emptyCart("Sean");
 ?>
